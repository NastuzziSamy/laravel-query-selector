<?php

namespace NastuzziSamy\Laravel\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use NastuzziSamy\Laravel\Exceptions\SelectionException;
use NastuzziSamy\Laravel\Utils\DateParsing;

/**
 * This trait add multiple scopes into model class
 * They are all usable directly by calling them (withtout the "scope" behind) when querying for items
 *
 * To work correctly, the developer must define at least this property:
 *  - `selection` as a key/value array
 *      => the developer defines as selectors as (s)he wants, but a selector is only usable if it is defined as key
 *      => each key is a selector: paginate, week, order...
 *      => each value corresponds to a default value for the selector
 *      => if a value is `null`, this means that the selector is optional
 *
 * It is also possible to customize these properties:
 *  - `paginateLimit` is the max amount of items in a page
 *  - `order_by` is the column name for the date creation
 *  - `begin_at` is the column name for the date of begining
 *  - `end_at` is the column name for the date of ending
 */
Trait HasSelection {
    /**
     * Paginate items by number of `number`
     * Auto manage page argument
     * @param  Builder $query
     * @param  int     $number (if $number > of the limit defined in the model => throw an exception)
     * @return Collection
     */
    public function scopePaginate(Builder $query, int $number) {
        if ($this->paginateLimit && $this->paginateLimit < $number)
            throw new SelectionException('Only '.$this->paginateLimit.' items could be displayed in the same time');

        return $query->paginate($number);
    }

    public function scopeGetPaginate(Builder $query, int $number) {
        return $this->scopePaginate($query, $number);
    }

    /**
     * Set a precise order
     * @param  Builder $query
     * @param  string  $order enum of `latest`, `oldest`, `random`, `a-z`, `z-a`
     * @return Builder
     */
    public function scopeOrder(Builder $query, string $order) {
        $orders = [
            'latest', 'oldest', 'random', 'a-z', 'z-a',
        ];

        switch ($order) {
            case 'latest':
                return $query->latest(
                    $this->getTable().'.'.($this->order_by ?? 'created_at')
                );

            case 'oldest':
                return $query->oldest(
                    $this->getTable().'.'.($this->order_by ?? 'created_at')
                );

            case 'random':
                return $query->inRandomOrder();

            case 'a-z':
                return $query->orderBy($this->name ?? 'name', 'asc');

            case 'z-a':
                return $query->orderBy($this->name ?? 'name', 'desc');
        }

        throw new SelectionException('This order '.$order.' does not exist. Only `'.implode('`, `', $orders).'` are allowed');
    }

    public function scopeGetOrder(Builder $query, string $order) {
        return $this->scopeOrder($query, $order)->get();
    }

    /**
     * Show items happened during the given date
     * @param  Builder $query
     * @param          $date    must be compatible with Carbon or an Exception will be thrown
     * @return Builder
     */
    public function scopeDate(Builder $query, $date, string $format = null) {
        $carbonDate = DateParsing::parse($date, $format);
        $carbonDate->hour = 0;
        $carbonDate->minute = 0;
        $carbonDate->second = 0;

        return $query
            ->where($this->getTable().'.'.($this->begin_at ?? 'created_at'), '>=', $carbonDate)
            ->where($this->getTable().'.'.($this->end_at ?? 'created_at'), '<=', $carbonDate->copy()->addDay());
    }

    public function scopeGetDate(Builder $query, $date, string $format = null) {
        return $this->scopeDay($query, $date, $format)->get();
    }

    /**
     * Show items happened during one of the given date
     * @param  Builder $query
     * @param          $date    must be compatible with Carbon or an Exception will be thrown
     * @return Builder
     */
    public function scopeDates(Builder $query, ...$dates) {
        $format = end($dates); // Last element must be a date format
        unset($dates[count($dates) - 1]);

        return $query->where(function ($query) use ($dates) {
            foreach ($dates as $date) {
                $carbonDate = DateParsing::parse($date, $format);
                $carbonDate->hour = 0;
                $carbonDate->minute = 0;
                $carbonDate->second = 0;

                $query = $query->orWhere(function ($query) use ($carbonDate) {
                    return $query
                        ->where($this->getTable().'.'.($this->begin_at ?? 'created_at'), '>=', $carbonDate)
                        ->where($this->getTable().'.'.($this->end_at ?? 'created_at'), '<=', $carbonDate->copy()->addDay());
                });
            }

            return $query;
        });
    }

    public function scopeGetDates(Builder $query, ...$dates) {
        return $this->scopeDay($query, $dates)->get();
    }

    /**
     * Show items within the given interval
     * @param  Builder $query
     * @param          $date    must be compatible with Carbon or an Exception will be thrown
     * @return Builder
     */
    public function scopeInterval(Builder $query, $date1, $date2, string $format = null) {
        list($carbonDate1, $carbonDate2) = DateParsing::interval($date1, $date2, $format, $format);

        return $query
            ->where($this->getTable().'.'.($this->begin_at ?? 'created_at'), '>=', $carbonDate1)
            ->where($this->getTable().'.'.($this->end_at ?? 'created_at'), '<=', $carbonDate2);
    }

    public function scopeGetInterval(Builder $query, $date1, $date2, string $format = null) {
        return $this->scopeDay($query, $date1, $date2, $format)->get();
    }

    // Group by

    /**
     * Show items within the given day
     * @param  Builder $query
     * @param          $date    must be compatible with Carbon or an Exception will be thrown
     * @return Builder
     */
    public function scopeDay(Builder $query, $date, string $format = null) {
        $carbonDate = DateParsing::parse($date, $format);

        return $query
            ->where($this->getTable().'.'.($this->begin_at ?? 'created_at'), '>=', $carbonDate)
            ->where($this->getTable().'.'.($this->end_at ?? 'created_at'), '<=', $carbonDate->copy()->addDay());
    }

    public function scopeGetDay(Builder $query, $date, string $format = null) {
        return $this->scopeDay($query, $date, $format)->get();
    }

    /**
     * Show items within the given week
     * @param  Builder $query
     * @param          $date    must be compatible with Carbon or an Exception will be thrown
     * @return Builder
     */
    public function scopeWeek(Builder $query, $date, string $format = null) {
        $carbonDate = DateParsing::parse($date, $format);

        return $query
            ->where($this->getTable().'.'.($this->begin_at ?? 'created_at'), '>=', $carbonDate)
            ->where($this->getTable().'.'.($this->end_at ?? 'created_at'), '<=', $carbonDate->copy()->addWeek());
    }

    public function scopeGetWeek(Builder $query, $date, string $format = null) {
        return $this->scopeWeek($query, $date, $format)->get();
    }

    /**
     * Show items within the given month
     * @param  Builder $query
     * @param          $date    must be compatible with Carbon or an Exception will be thrown
     * @return Builder
     */
    public function scopeMonth(Builder $query, $date, string $format = null) {
        $carbonDate = DateParsing::parse($date, $format);

        return $query
            ->where($this->getTable().'.'.($this->begin_at ?? 'created_at'), '>=', $carbonDate)
            ->where($this->getTable().'.'.($this->end_at ?? 'created_at'), '<=', $carbonDate->copy()->addMonth());
    }

    public function scopeGetMonth(Builder $query, $date, string $format = null) {
        return $this->scopeMonth($query, $date, $format)->get();
    }

    /**
     * Show items within the given year
     * @param  Builder $query
     * @param          $date    must be compatible with Carbon or an Exception will be thrown
     * @return Builder
     */
    public function scopeYear(Builder $query, $date, string $format = null) {
        $carbonDate = DateParsing::parse($date, $format);

        return $query
            ->where($this->getTable().'.'.($this->begin_at ?? 'created_at'), '>=', $carbonDate)
            ->where($this->getTable().'.'.($this->end_at ?? 'created_at'), '<=', $carbonDate->copy()->addYear());
    }

    public function scopeGetYear($query, $date, string $format = null) {
        return $this->scopeYear($query, $date, $format)->get();
    }

    /**
     * Get query builder to show items with the different selectors defined in the model
     * @param  Builder $query
     * @return Builder
     */
    public function scopeSelect(Builder $query) {
        if ($this->selection) {
            $dateSelection = null;
            $dateSelectors = [
                'day', 'week', 'month', 'year',
            ];

            foreach ($this->selection as $selector => $default) {
                $param = \Request::input($selector, $default);

                if ($selector === 'paginate' || $param === null) // Paginate returns a collection
                    continue;

                if (in_array($selector, $dateSelectors) && ($this->uniqueDateSelector ?? true)) {
                    if (!\Request::filled($selector))
                        continue;

                    if ($dateSelection)
                        throw new SelectionException('Can\'t set the selector '.$selector.' after the selector '.$dateSelection);

                    $dateSelection = $selector;
                }

                try {
                    $query = $this->{'scope'.ucfirst($selector)}(
                        $query,
                        ...explode(',', $param)
                    );
                } catch (\Error $e) {
                    throw new SelectionException('More parameters (spaced by `,`) are expected for the selector '.$selector);
                }

                if (!($query instanceof Builder)) // In case where a selector returns a data directly
                    return $query;
            }

            if (in_array('paginate', array_keys($this->selection)) && \Request::input('paginate', $this->selection['paginate'])) { // Must be treated at last
                return new Collection($this->scopePaginate(
                    $query,
                    \Request::input('paginate', $this->selection['paginate'])
                )->items());
            }
        }

        return $query;
    }

    /**
     * Show all items with the different selectors defined in the model
     * @param  Builder $query
     * @return Collection
     */
    public function scopeGetSelection(Builder $query, bool $allowEmptySelection = false) {
        $selection = $this->scopeSelect($query);
        $collection = $selection instanceof Builder ? $selection->get() : $selection;

        if ((is_null($collection) || count($collection) === 0) && !(($this->selectionCanBeEmpty ?? false) || $allowEmptySelection))
            throw new SelectionException('The selection is maybe too constraining or the page is empty', 416);

        return $collection;
    }
}
