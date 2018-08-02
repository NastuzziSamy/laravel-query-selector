<?php

namespace NastuzziSamy\Laravel\Traits;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use NastuzziSamy\Laravel\Exceptions\SelectionException;

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
     * @param  string  $order enum of `latest`, `oldest` and `random`
     * @return Builder
     */
    public function scopeOrder(Builder $query, string $order) {
        $orders = [
            'latest'    => 'latest',
            'oldest'    => 'oldest',
            'random'    => 'inRandomOrder'
        ];

        if (!isset($orders[$order]))
            throw new SelectionException('This order '.$order.' does not exist. Only `latest`, `oldest` and `random` are allowed');

        if ($order === 'random')
            return $query->inRandomOrder();
        else {
            return $query->{$orders[$order]}(
                $this->order_by ?? 'created_at'
            );
        }
    }

    public function scopeGetOrder(Builder $query, string $order) {
        return $this->scopeOrder($query, $order)->get();
    }

    /**
     * Show items within the day given
     * @param  Builder $query
     * @param          $date    must be compatible with Carbon or an Exception will be thrown
     * @return Builder
     */
    public function scopeDay(Builder $query, $date) {
        return $query
            ->where($this->begin_at ?? 'created_at', '>=', Carbon::parse($date))
            ->where($this->end_at ?? 'created_at', '<=', Carbon::parse($date)->addDay());
    }

    public function scopeGetDay(Builder $query, $date) {
        return $this->scopeDay($query, $date)->get();
    }

    /**
     * Show items within the week given
     * @param  Builder $query
     * @param          $date    must be compatible with Carbon or an Exception will be thrown
     * @return Builder
     */
    public function scopeWeek(Builder $query, $date) {
        return $query
            ->where($this->begin_at ?? 'created_at', '>=', Carbon::parse($date))
            ->where($this->end_at ?? 'created_at', '<=', Carbon::parse($date)->addWeek());
    }

    public function scopeGetWeek(Builder $query, $date) {
        return $this->scopeWeek($query, $date)->get();
    }

    /**
     * Show items within the month given
     * @param  Builder $query
     * @param          $date    must be compatible with Carbon or an Exception will be thrown
     * @return Builder
     */
    public function scopeMonth(Builder $query, $date) {
        return $query
            ->where($this->begin_at ?? 'created_at', '>=', Carbon::parse($date))
            ->where($this->end_at ?? 'created_at', '<=', Carbon::parse($date)->addMonth());
    }

    public function scopeGetMonth(Builder $query, $date) {
        return $this->scopeMonth($query, $date)->get();
    }

    /**
     * Show items within the year given
     * @param  Builder $query
     * @param          $date    must be compatible with Carbon or an Exception will be thrown
     * @return Builder
     */
    public function scopeYear(Builder $query, $date) {
        return $query
            ->where($this->begin_at ?? 'created_at', '>=', Carbon::parse($date))
            ->where($this->end_at ?? 'created_at', '<=', Carbon::parse($date)->addYear());
    }

    public function scopeGetYear($query, $date) {
        return $this->scopeYear($query, $date)->get();
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

            if (in_array('paginate', array_keys($this->selection)) && $this->selection['paginate']) { // Must be treated at last
                return $this->scopePaginate(
                    $query,
                    \Request::input('paginate', $this->selection['paginate'])
                );
            }
        }

        return $query;
    }

    /**
     * Show all items with the different selectors defined in the model
     * @param  Builder $query
     * @return Collection
     */
    public function scopeGetSelection(Builder $query) {
        $selection = $this->scopeSelect($query);
        $collection = $selection instanceof Builder ? $selection->get() : $selection;

        if ((is_null($collection) || count($collection) === 0) && !($this->selectionCanBeEmpty ?? false))
            throw new SelectionException('The selection is maybe too constraining or the page is empty', 416);

        return $collection;
    }
}
