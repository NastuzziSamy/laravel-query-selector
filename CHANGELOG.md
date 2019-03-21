# Changelog
## 2.3.0
- Add new scopes: first and find selectors

## 2.2.0
- Change private methods to protected
- Security issue: Avoid unfilled queries
- Move Changelog

## 2.1.1
- Bug fix: ms timestamps was not accepted

## 2.1.0
- Ading filter query to make researches

## 2.0.0
- Refactoring of all parameters (could break from v1)
- Implement and adapt to array queries for a future feature
    * ex: `?filter[x]=0&filter[y]=1`
    * The scope is called for each (x and y) and give the params: `x, 0` and `y, 1`

## 1.4.1
- It is now possible to order by alpha
- DateParsing dependancy missing

## 1.4.0
- Bug fix: timestamp dates where not recognized
- DateParsing class created to simplify date transformation to Carbon date
- Each date selector accept a new parameter: the date format (by default, Carbon will try to resolve the date by parsing it)
- Add new selectors:
    - Date: get items happened in this date, and only this one
    - Dates: get items happened in one of the dates (the last argument must be a date format)
    - Interval: get items within the interval

## 1.3.6
- Correct pagination return

## v1.3.5
- Allowing empty values directly in `getSelection`

## v1.3.4
- Bug when paginate was null

## v1.3.3
- Add multiple params
- Custom scopes are usable

## v1.3.2
- Too late/tired to push ?
- Fix a lot of bugs and misspells

## v1.3.1
- Forget to return $collection...

## v1.3.0
- Add a restriction to the result: the selection must return at least one item or throw an error
- This behavior can be changed by setting the `selectionCanBeEmpty` property
- SelectionException is now an HttpException (from Symfony)

## v1.2.2
- Correct wrong variable names..

## v1.2.1
- Setting a default value for a date could create unexpected Exceptions
- Change `created_at` to `order_by`

## v1.2.0
- Add a custom Exception: SelectionException
- The developer can now know if a problem happened during the selection

## v1.1.1
- Throw now an Exception if the user tries to set mutiple date restrictions

## v1.1.0
- Add one date selector restriction: avoid from setting multiple date restrictions
- Advice:
    * Define in the `selection` array optional params first
    * Define a unique date selector with a default value
    * Put in first which date restriction should be concidered first
