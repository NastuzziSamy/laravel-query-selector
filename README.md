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

To work correctly, the developer must define this property:
- `selection` as a key/value array
  => the developer defines as selectors as (s)he wants, but a selector is only usable if it is defined as key
  => each key is a selector: paginate, week, order...
  => each value can be
    - a simple value (which is treated as like a default value)
    - an array with (if needed) a `default` key. Next, each selector as its column params
  => if the default value is `null` or it is not defined if the array, this means that the selector is optional

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
        'paginate'  => 2, // 2 users per page by default
        'order'     => [
            'default' => 'latest',
            'columns' => [
                'creation'  => 'id' // We set our column to order our data
            ]
        ]
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
[
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
]
```

### Example 2: request /api/users?paginate=3&order=random

Output:
```json
[
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
]
```

### Custom: You can also use your custom scopes !!

If you have installed the `nastuzzi-samy\laravel-model-stages` for example

#### Usage example

In your targeted model:
```php
<?php

namespace \App\Models;

use Illuminate\Database\Eloquent\Model;
use NastuzziSamy\Laravel\Traits\HasSelection;
use NastuzziSamy\Laravel\Traits\HasStages;

class User extends Model {
    use HasSelection, HasStages;

    protectec $selection = [
        'stage' => null, // Optional selector because default value is null
        'stages' => [], // Array option but no default value
        'order' => 'oldest',
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

    public function index($request) {
        return response->json(
            User::getSelecion()
        );
    }

    /* ... */
}
```

Let think we got a company tree with 1 Boss, 2 Supervisors and 4 Employees

### Example 1: request /api/users?stage=0

Output:
```json
[
    {
        "id": 1,
        "name": "Boss",
        "parent_id": null
    },
]
```

### Example 2: request /api/users?stage=0,1

Output:
```json
[
    {
        "id": 1,
        "name": "Boss",
        "parent_id": null
    },
    {
        "id": 2,
        "name": "Supervisor 1",
        "parent_id": 1
    },
    {
        "id": 3,
        "name": "Supervisor 2",
        "parent_id": 1
    },
]
```

### Example 3: request /api/users?stage=2&order=random

Output:
```json
[
    {
        "id": 6,
        "name": "Employee 3",
        "parent_id": 3
    },
    {
        "id": 4,
        "name": "Employee 1",
        "parent_id": 2
    },
    {
        "id": 7,
        "name": "Employee 4",
        "parent_id": 3
    },
    {
        "id": 5,
        "name": "Employee 2",
        "parent_id": 2
    },
]
```



## Changelog
### 2.1.0
- Ading filter query to make researches

### 2.0.0
- Refactoring of all parameters (could break from v1)
- Implement and adapt to array queries for a future feature
    * ex: `?filter[x]=0&filter[y]=1`
    * The scope is called for each (x and y) and give the params: `x, 0` and `y, 1`

### 1.4.1
- It is now possible to order by alpha
- DateParsing dependancy missing

### 1.4.0
- Bug fix: timestamp dates where not recognized
- DateParsing class created to simplify date transformation to Carbon date
- Each date selector accept a new parameter: the date format (by default, Carbon will try to resolve the date by parsing it)
- Add new selectors:
    - Date: get items happened in this date, and only this one
    - Dates: get items happened in one of the dates (the last argument must be a date format)
    - Interval: get items within the interval

### 1.3.6
- Correct pagination return

### v1.3.5
- Allowing empty values directly in `getSelection`

### v1.3.4
- Bug when paginate was null

### v1.3.3
- Add multiple params
- Custom scopes are usable

### v1.3.2
- Too late/tired to push ?
- Fix a lot of bugs and misspells

### v1.3.1
- Forget to return $collection...

### v1.3.0
- Add a restriction to the result: the selection must return at least one item or throw an error
- This behavior can be changed by setting the `selectionCanBeEmpty` property
- SelectionException is now an HttpException (from Symfony)

### v1.2.2
- Correct wrong variable names..

### v1.2.1
- Setting a default value for a date could create unexpected Exceptions
- Change `created_at` to `order_by`

### v1.2.0
- Add a custom Exception: SelectionException
- The developer can now know if a problem happened during the selection

### v1.1.1
- Throw now an Exception if the user tries to set mutiple date restrictions

### v1.1.0
- Add one date selector restriction: avoid from setting multiple date restrictions
- Advice:
    * Define in the `selection` array optional params first
    * Define a unique date selector with a default value
    * Put in first which date restriction should be concidered first
