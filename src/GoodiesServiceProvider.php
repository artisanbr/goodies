<?php
/*
 * Copyright (c) 2023. Tanda Interativa - Todos os Direitos Reservados
 * Desenvolvido por Renalcio Carlos Jr.
 */

namespace ArtisanBR\Goodies;

use SplFileInfo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Stringable;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder as EBuilder;

class GoodiesServiceProvider extends ServiceProvider
{

    private string $config_path = __DIR__ . '/../config/';


    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //Configs
        foreach (File::allFiles($this->config_path) as $file) {

            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->mergeConfigFrom(
                    $file->getPathname(), str($file->getRelativePathname())->replace('/', '.')->beforeLast('.php')->toString()
                );
            }


        }

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
                $this->macros();
        $this->publishes(collect(File::allFiles($this->config_path))
                             ->mapWithKeys(fn($file): array => [
                                 $file->getPathname() => config_path($file->getRelativePathname()),
                             ])->toArray());

    }

    protected function macros(){
        //Str

        Stringable::macro('limitPath', function (string $path, int $keepFirst = 1, int $keepLast = 2, string $ellipsis = '...'): string {
            $segments = explode('/', $path);

            if (count($segments) <= ($keepFirst + $keepLast)) {
                return $path;
            }

            $start = array_slice($segments, 0, $keepFirst);
            $end = array_slice($segments, -$keepLast);

            return implode('/', [...$start, $ellipsis, ...$end]);
        });

        // region Eloquent Macros

        EBuilder::macro('whereAnyLike', function (array|string $attributes, string $searchTerm, bool $sensitive = false) {

            if (! $sensitive) {
                $searchTerm = Str::lower($searchTerm);
            }

            $searchTerm = str($searchTerm)->startsWith('%') || str($searchTerm)->endsWith('%') ? $searchTerm : "%{$searchTerm}%";

            $this->where(function (EBuilder $query) use ($attributes, $searchTerm, $sensitive) {
                foreach (Arr::wrap($attributes) as $attribute) {
                    $query->when(
                        Str::contains($attribute, '.'),
                        function (EBuilder $query) use ($attribute, $searchTerm, $sensitive) {
                            [$relationName, $relationAttribute] = explode('.', $attribute);

                            $query->orWhereHas($relationName, function (EBuilder $query) use ($relationAttribute, $searchTerm, $sensitive) {
                                /*if(!$sensitive){
                                    $relationAttribute = DB::raw('lower(product)');
                                }*/
                                $query->where(! $sensitive ? DB::raw("lower({$relationAttribute})") : $relationAttribute, 'LIKE', $searchTerm);
                            });
                        },
                        function (EBuilder $query) use ($attribute, $searchTerm, $sensitive) {
                            $query->orWhere(! $sensitive ? DB::raw("lower({$attribute})") : $attribute, 'LIKE', $searchTerm);
                        }
                    );
                }
            });

            return $this;
        });
        // endregion

        // region Collection macros

        Collection::macro('fromFilamentBuilder', function (string $pivot = 'type') {
            return $this
                ->values()
                ->map(function ($item) use ($pivot) {
                    // dump($item);

                    $type = $item['type'] ?? null;

                    $data = $item['data'] ?? [];

                    if (! $type && ! $data) {
                        return $item;
                    }

                    return [
                        $pivot => $type,
                        ...$data,
                    ];
                })->filter();
        });

        Collection::macro('toFilamentBuilder', function (string $pivot = 'type') {
            return $this
                ->values()
                ->map(function ($item) use ($pivot) {

                    $type = null;

                    if (is_object($item)) {
                        $item = method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
                    }

                    $type = $item[$pivot] ?? null;

                    if (! $type) {
                        return $item;
                    }

                    unset($item['data']);

                    return [
                        'type' => $type,
                        'data' => $item, // item sem a key 'data',
                    ];
                })->filter();
        });

        Collection::macro('even', function () {
            return $this->filter(static fn ($item, $key) => $key % 2 === 0);
        });

        Collection::macro('odd', function () {
            return $this->filter(static fn ($item, $key) => $key % 2 !== 0);
        });

        Collection::macro('mapRecursive', function (callable $callback) {
            $items = $this->toArray();
            array_walk_recursive($items, $callback);

            return new static($items);
        });
        Collection::macro('keyByValues', function () {
            return $this->keyBy(fn ($item) => $item);
        });

        Collection::macro('dataGet', function ($key, $default = null) {
            return collect(data_get($this->toArray(), $key, $default));
        });

        Collection::macro('toExtensions', function () {
            return $this->map(function ($item) {
                try {
                    return ".{$item}";
                } catch (\Exception $e) {
                    dd($item);
                }
            });
        });

        /*Collection::macro('whereLike', function (array|string $attributes, array|string $searchTerm, ?callable $escape = null) {
            return $this->filter(function ($item) use ($attributes, $searchTerm, $escape) {

                $attributes = Collection::wrap($attributes)->toArray();

                $searchTerms = Collection::wrap($searchTerm)->map(fn($st) => Str::lower($st))->values()->toArray();

                foreach ($attributes as $attr) {
                    if (isset($item[$attr]) && Str::contains(Str::lower($item[$attr]), $searchTerms)) {
                        return true;
                    }
                    else if (($escape ?? false) && is_callable($escape) && $escape($item)) {
                        return true;
                    }
                }

                return false;
            });
        });*/

        Collection::macro('whereLike', function (array|string $attributes, array|string $searchTerm, ?callable $escape = null) {
            // 1. OTIMIZAÇÃO: Preparação dos dados FORA do loop de filtro.
            // Isso evita reprocessar a estrutura de busca a cada iteração.
            $targetAttributes = Arr::wrap($attributes);

            $searchTerms = collect(Arr::wrap($searchTerm))
                ->filter(fn ($t) => $t !== null && $t !== '') // Remove termos nulos/vazios
                ->map(fn ($t) => Str::lower((string) $t))
                ->values()
                ->all();

            // Se não houver termos de busca, decide-se se retorna tudo ou nada.
            // Aqui retornamos a coleção inteira (comportamento comum de filtros vazios).
            // Se preferir retornar vazio, troque por: if (empty($searchTerms)) return $this->newQuery()->make([]);
            if (empty($searchTerms)) {
                return $this;
            }

            return $this->filter(function ($item) use ($targetAttributes, $searchTerms, $escape) {
                // 2. LÓGICA: Verifica o Escape primeiro.
                // Se o escape for verdadeiro, retorna true imediatamente (Curto-circuito).
                if ($escape && is_callable($escape) && $escape($item)) {
                    return true;
                }

                foreach ($targetAttributes as $attribute) {
                    // 3. SUPORTE HÍBRIDO (Array/Objeto): data_get()
                    // Funciona em arrays, objetos, Eloquent Models e suporta 'ponto' (ex: 'categoria.nome')
                    $value = data_get($item, $attribute);

                    if (is_null($value)) {
                        continue;
                    }

                    // Converte valor para string e minúsculo
                    $valueStr = Str::lower((string) $value);

                    // Str::contains já aceita um array de termos (searchTerms) desde o Laravel 7+
                    if (Str::contains($valueStr, $searchTerms)) {
                        return true;
                    }
                }

                return false;
            });
        });
        // endregion

        // region DB Builder Macros
        Builder::macro('whereLikeAny', function (array|string $attributes, array|string $searchTerm) {

            $terms = Collection::wrap($searchTerm);

            foreach ($terms as $term) {
                $term = str($term)->contains('%') || str($term)->endsWith('%') ? $term : "%{$term}%";
                $this->where(function (Builder $query) use ($attributes, $term) {
                    foreach (Arr::wrap($attributes) as $attribute) {
                        $query->orWhereLike($attribute, $term);
                    }
                });
            }

            return $this;
        });
        // endregion
    }
}
