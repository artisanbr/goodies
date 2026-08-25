# Goodies

[![Latest Version](https://img.shields.io/badge/version-v0.0.4-blue.svg)](composer.json)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x%20%7C%2013.x-ff2d20.svg)](composer.json)

Um conjunto de "goodies" (utilitários) para projetos Laravel da **Artisan Digital**: macros para os componentes nativos do framework, traits para Enums, traits/observers para Models e alguns helpers globais de PHP e Blade.

## Índice

- [Instalação](#instalação)
- [Configuração](#configuração)
- [Macros](#macros)
    - [Stringable](#stringable)
    - [Eloquent Builder](#eloquent-builder)
    - [Query Builder (DB)](#query-builder-db)
    - [Collection](#collection)
- [Traits para Enums](#traits-para-enums)
    - [EnumToArray](#enumtoarray)
    - [EnumBase](#enumbase)
    - [EnumWithTitles](#enumwithtitles)
- [Traits e Observers para Models](#traits-e-observers-para-models)
    - [BelongsToUser + BelongsToUserObserver](#belongstouser--belongstouserobserver)
    - [SelfRelationship](#selfrelationship)
- [Helpers globais](#helpers-globais)
- [Helpers de componentes Blade](#helpers-de-componentes-blade)
- [Licença](#licença)

## Instalação

Instale o pacote via Composer:

```bash
composer require artisanbr/goodies
```

O pacote utiliza [package discovery](https://laravel.com/docs/packages#package-discovery) do Laravel, então o `GoodiesServiceProvider` é registrado automaticamente. Nenhuma configuração adicional é necessária para usar as macros e os helpers globais.

## Configuração

Se quiser customizar as opções do pacote, publique o arquivo de configuração:

```bash
php artisan vendor:publish --provider="ArtisanBR\Goodies\GoodiesServiceProvider"
```

Isso irá gerar `config/goodies.php`:

```php
return [
    'user_relation_key' => 'user_id',
];
```

- `user_relation_key`: nome da coluna usada como foreign key do usuário logado pela trait [`BelongsToUser`](#belongstouser--belongstouserobserver) e pelo `BelongsToUserObserver`.

## Macros

Todas as macros abaixo são registradas automaticamente no `boot()` do service provider — basta ter o pacote instalado para usá-las.

### Stringable

#### `Stringable::limitPath()`

Encurta um caminho de arquivo/URL mantendo os primeiros e últimos segmentos, útil para exibir paths longos de forma legível.

```php
str('app/Http/Controllers/Api/V1/UserController.php')
    ->limitPath(keepFirst: 1, keepLast: 2);
// "app/.../V1/UserController.php"
```

Assinatura: `limitPath(string $path, int $keepFirst = 1, int $keepLast = 2, string $ellipsis = '...'): string`

### Eloquent Builder

#### `whereAnyLike()`

Busca (`LIKE`) em múltiplos atributos — inclusive em relacionamentos, usando a notação `relacionamento.atributo` — combinados com `OR`.

```php
User::query()
    ->whereAnyLike(['name', 'email', 'profile.bio'], 'joão')
    ->get();

// case-sensitive
User::query()->whereAnyLike('name', 'João', sensitive: true)->get();
```

Assinatura: `whereAnyLike(array|string $attributes, string $searchTerm, bool $sensitive = false)`

### Query Builder (DB)

#### `whereLikeAny()`

Equivalente ao `whereAnyLike()`, mas para o Query Builder puro (`DB::table()`), aceitando um ou vários termos de busca.

```php
DB::table('users')
    ->whereLikeAny(['name', 'email'], ['joão', 'maria'])
    ->get();
```

Assinatura: `whereLikeAny(array|string $attributes, array|string $searchTerm)`

### Collection

#### `whereLike()`

Filtra uma `Collection` (de arrays, objetos ou Models) verificando se algum dos atributos contém qualquer um dos termos informados. Suporta notação em "ponto" (`categoria.nome`) via `data_get()` e um callback opcional de escape.

```php
collect($items)->whereLike(['name', 'category.name'], 'eletrônico');

collect($items)->whereLike('name', ['a', 'b'], escape: fn ($item) => $item['featured']);
```

#### `even()` / `odd()`

Filtram os itens pelo índice (chave) par ou ímpar.

```php
collect([1, 2, 3, 4])->even(); // [0 => 1, 2 => 3]
collect([1, 2, 3, 4])->odd();  // [1 => 2, 3 => 4]
```

#### `mapRecursive()`

Aplica um callback recursivamente em todos os valores da coleção (via `array_walk_recursive`).

```php
collect(['a' => 1, 'b' => ['c' => 2]])->mapRecursive(function (&$value) {
    $value *= 10;
});
```

#### `keyByValues()`

Atalho para `keyBy(fn ($item) => $item)` — indexa a coleção pelos próprios valores.

```php
collect(['a', 'b', 'c'])->keyByValues();
// ['a' => 'a', 'b' => 'b', 'c' => 'c']
```

#### `dataGet()`

Atalho para `data_get()` sobre o array da coleção, já retornando uma nova `Collection`.

```php
collect(['user' => ['name' => 'João']])->dataGet('user.name');
```

#### `toExtensions()`

Prefixa cada item da coleção com um ponto, transformando uma lista de extensões em formato `.ext`.

```php
collect(['pdf', 'jpg', 'png'])->toExtensions();
// ['.pdf', '.jpg', '.png']
```

#### `fromFilamentBuilder()` / `toFilamentBuilder()`

Convertem coleções entre o formato "flat" e o formato `{type, data}` usado pelo [Filament Builder field](https://filamentphp.com), útil ao persistir/ler dados de blocos dinâmicos do Filament fora do próprio field.

```php
// De [{'type' => 'text', 'data' => ['value' => 'foo']}, ...] para [{'type' => 'text', 'value' => 'foo'}, ...]
collect($filamentBuilderState)->fromFilamentBuilder();

// E de volta ao formato do Filament Builder
collect($flatItems)->toFilamentBuilder();
```

## Traits para Enums

Traits pensadas para Enums nativos do PHP (`enum ... : string`), acrescentando conveniências comuns em Enums de Laravel.

### `EnumToArray`

```php
use ArtisanBR\Goodies\Enums\Traits\EnumToArray;

enum Status: string
{
    use EnumToArray;

    case Active = 'active';
    case Inactive = 'inactive';
}

Status::names();  // ['Active', 'Inactive']
Status::values(); // ['active', 'inactive']
Status::array();  // ['active' => 'Active', 'inactive' => 'Inactive']
Status::toArray(); // idem a array()
```

### `EnumBase`

Já inclui `EnumToArray` e adiciona comparações rápidas com o `value` do Enum.

```php
use ArtisanBR\Goodies\Enums\Traits\EnumBase;

enum Status: string
{
    use EnumBase;

    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';
}

$status = Status::Active;

$status->is('active');               // true
$status->isAny('inactive', 'blocked'); // false
```

### `EnumWithTitles`

Gera títulos legíveis (e traduzíveis via `__()`) a partir do nome de cada case, separando palavras em PascalCase/camelCase.

```php
use ArtisanBR\Goodies\Enums\Traits\EnumWithTitles;

enum Status: string
{
    use EnumWithTitles;

    case AwaitingPayment = 'awaiting_payment';
    case Shipped = 'shipped';
}

Status::AwaitingPayment->title(); // "Awaiting Payment"
Status::titles();
// ['awaiting_payment' => 'Awaiting Payment', 'shipped' => 'Shipped']
```

> As três traits podem ser combinadas normalmente com `use EnumBase, EnumWithTitles;` em um mesmo Enum.

## Traits e Observers para Models

### `BelongsToUser` + `BelongsToUserObserver`

A trait `BelongsToUser` adiciona um relacionamento `user()` (e o alias `owner()`) ao Model, usando o model de usuário configurado em `auth.providers.users.model`. O `BelongsToUserObserver` preenche automaticamente a foreign key do usuário logado ao criar um novo registro.

A coluna usada é resolvida nesta ordem: propriedade estática `$userRelationKey` no Model → `config('goodies.user_relation_key')` → `'user_id'`.

```php
use ArtisanBR\Goodies\Traits\BelongsToUser;
use ArtisanBR\Goodies\Observers\BelongsTo\BelongsToUserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(BelongsToUserObserver::class)]
class Post extends Model
{
    use BelongsToUser;

    // opcional: customiza o nome da coluna para este model
    public static string $userRelationKey = 'author_id';
}
```

```php
$post = Post::create(['title' => 'Olá mundo']);
$post->user_id; // preenchido automaticamente com o id do usuário autenticado

$post->user;  // relacionamento BelongsTo
$post->owner; // alias de user()
```

Caso prefira registrar o observer manualmente (sem o atributo `#[ObservedBy]`), use `Post::observe(BelongsToUserObserver::class)` no `boot()` de um `EventServiceProvider`/`AppServiceProvider`.

### `SelfRelationship`

Adiciona uma relação hierárquica simples (pai/filhos) a um Model que referencia a si mesmo através de uma coluna `parent_id`, além de scopes para filtrar por hierarquia.

```php
use ArtisanBR\Goodies\Traits\SelfRelationship;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use SelfRelationship;
}
```

```php
$category->parent;   // BelongsTo self, via parent_id
$category->children; // HasMany self, via parent_id

Category::whereParent($categoryId)->get(); // filhos diretos de um registro
Category::whereIsParent()->get();          // apenas registros raiz (parent_id null)
```

## Helpers globais

Funções globais carregadas automaticamente pelo Composer (`files` autoload).

#### `array_filter_recursive()`

Igual ao `array_filter()` nativo, mas aplicado recursivamente em arrays aninhados.

```php
array_filter_recursive([
    'a' => 1,
    'b' => ['c' => 0, 'd' => 2],
]);
// ['a' => 1, 'b' => ['d' => 2]]
```

Assinatura: `array_filter_recursive(array $array, ?callable $callback = null, int $mode = 0): array`

## Helpers de componentes Blade

Utilitários para agrupar atributos de componentes Blade por prefixo — útil para repassar, por exemplo, todos os atributos `wrapper-*` para o elemento wrapper de um componente e `input-*` para o input interno.

#### `attributesBagGroup()`

```blade
{{-- <x-field wrapper-class="mb-4" input-placeholder="Digite aqui" /> --}}

@php
    $wrapperAttrs = attributesBagGroup('wrapper', $attributes);
    $inputAttrs = attributesBagGroup('input', $attributes);
@endphp

<div {{ $wrapperAttrs }}>
    <input {{ $inputAttrs }} />
</div>
```

Também aceita um array de prefixos, retornando os atributos que casarem com qualquer um deles:

```php
attributesBagGroup(['wrapper', 'container'], $attributes);
```

Assinatura: `attributesBagGroup(string|array $groupName, ComponentAttributeBag $attributes): ComponentAttributeBag`

### `BladeComponentPrefix`

Classe invocável utilitária para prefixar nomes de componentes Blade dinamicamente (por exemplo, ao registrar um namespace de componentes de um pacote).

```php
use ArtisanBR\Goodies\Support\Blade\BladeComponentPrefix;

$resolver = new BladeComponentPrefix('goodies');

$resolver('button'); // "goodies-button"

$resolver = new BladeComponentPrefix(null);
$resolver('button'); // "button" (sem prefixo)
```

## Licença

Este pacote é open-source e distribuído sob a [licença MIT](LICENSE).
