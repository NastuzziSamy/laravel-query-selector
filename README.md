# laravel-query-selector

A Laravel package that add some custom scopes on models to make a better selection via requests

## Installation
### With composer

```bash
composer require NastuzziSamy/laravel-query-selector
```

## Docs

This trait add multiple scopes into model class
They are all usable directly by calling them (withtout the "scope" behind) when querying for items

To work correctly, the developer must define at least this property:
- `selection` as a key/value array
    * the developer defines as selectors as (s)he wants, but a selector is only usable if it is defined as key
    * each key is a selector: paginate, week, order...
    * each value corresponds to a default value for the selector
    * if a value is `null`, this means that the selector is optional

It is also possible to customize these properties:
- `paginateLimit` is the max amount of items in a page
- `created_at` is the column name for the date creation
- `begin_at` is the column name for the date of begining
- `end_at` is the column name for the date of ending

## Usage

In your targeted model:
```php
<?php

namespace \App\Models;

use Illuminate\Database\Eloquent\Model;
use NastuzziSamy\Laravel\Traits\HasSelection;

class User extends Model {
    use HasSelection;

    // Example of selection definition
    protected $selection = [
        'paginate'  => 2, // 2 users per page
        'order'     => 'latest'
    ];

    /* ... */
}
```

In your targeted controller:
```php
<?php

namespace \App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\User;

class UserController extends Controller {
    /* ... */

    public function index() {
        return response->json(
            User::getSelection()
        );
    }

    /* ... */
}
```

Let think we got 11 users, the 11th is the latest

### Example 1: request /api/users?paginate=5

Output:
```json
{
    {
        "id": 11,
        "name": "11"
    },
    {
        "id": 10,
        "name": "10"
    },
    {
        "id": 9,
        "name": "9"
    },
    {
        "id": 8,
        "name": "8"
    },
    {
        "id": 7,
        "name": "7"
    },
}
```

### Example 2: request /api/users?paginate=3&order=random

Output:
```json
{
    {
        "id": 4,
        "name": "4"
    },
    {
        "id": 10,
        "name": "10"
    },
    {
        "id": 3,
        "name": "3"
    },
}
```
