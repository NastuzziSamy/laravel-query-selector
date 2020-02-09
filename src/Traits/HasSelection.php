<?php

namespace NastuzziSamy\Laravel\Traits;

use Illuminate\Support\{
    Collection, Facades\Request
};
use Illuminate\Database\Eloquent\Builder;
use NastuzziSamy\Laravel\Exceptions\SelectionException;
use NastuzziSamy\Laravel\Utils\DateParsing;

/**
 * This trait add multiple scopes into model class
 * They are all usable directly by calling them (withtout the "scope" behind) when querying for items
 *
 * To work correctly, the developer must define this property:
 *  - `selection` as a key/value array
 *      => the developer defines as selectors as (s)he wants, but a selector is only usable if it is defined as key
 *      => each key is a selector: paginate, week, order...
 *      => each value can be
 *          - a simple value (which is treated as like a default value)
 *          - an array with (if needed) a `default` key. Next, each selector as its column params
 *      => if the default value is `null` or it is not defined if the array, this means that the selector is optional
 */
trait HasSelection {
    protected function getSelectionOption(string $key, $default = null) {
        if (isset($this->selection)) {
            $value = $this->selection;

            foreach (explode('.', $key) as $name) {
                if (!isset($value[$name]))
                    return $default;

                $value = $value[$name];
            }

            return $value;
        }
        else
            return $default;
    }

    /**
     * Paginate items by number of `number`
     * Auto manage page argument
     * @param  Builder $query
     * @param  int     $number (if $number > of the limit defined in the model => throw an exception)
     * @return Collection
     */
    public function scopePaginate(Builder $query, int $number) {
        $limit = $this->getSelectionOption('paginate.limit');

        if ($limit && $limit < $number)
            throw new SelectionException('Only '.$limit.' items could be displayed in the same time');

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
                    $this->getTable().'.'.$this->getSelectionOption('order.columns.date', 'created_at')
                );

            case 'oldest':
                return $query->oldest(
                    $this->getTable().'.'.$this->getSelectionOption('order.columns.date', 'created_at')
                );

            case 'random':
                return $query->inRandomOrder();

            case 'a-z':
                return $query->orderBy($this->getSelectionOption('order.columns.name', 'name'), 'asc');

            case 'z-a':
                return $query->orderBy($this->getSelectionOption('order.columns.name', 'name'), 'desc');
        }

        throw new SelectionException('This order '.$order.' does not exist. Only `'.implode('`, `', $orders).'` are allowed');
    }

    public function scopeGetOrder(Builder $query, string $order) {
        return $this->scopeOrder($query, $order)->get();
    }

    public function scopeFilter(Builder $query, string $filter, string $value, ...$options) {
        if (is_array($this->getSelectionOption('filter.authorized')) && !$this->getSelectionOption('filter.authorized.'.$filter))
            throw new SelectionException('The filter '.$filter.' is not allowed');

        $begin = in_array('begin', $options) ? '' : '%';
        $end = in_array('end', $options) ? '' : '%';
        $like = in_array('insensitive', $options) ? 'like' : 'like binary';

        if (in_array('word', $options))
            $value = explode(' ', $value);
        else if (in_array('caracter', $options))
            $value = str_split($value);

        if (!is_array($value))
            $value = [$value];

        for ($i = 0; $i < count($value); $i++)
            $query = $query->where($filter, $like, ($i === 0 ? $begin : '%').$value[$i].($i + 1 === count($value) ? $end : '%'));

        return $query;
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
            ->where($this->getTable().'.'.$this->getSelectionOption('date.columns.begin', 'created_at'), '>=', $carbonDate)
            ->where($this->getTable().'.'.$this->getSelectionOption('date.columns.end', 'created_at'), '<=', $carbonDate->copy()->addDay());
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
                        ->where($this->getTable().'.'.$this->getSelectionOption('dates.columns.begin', 'created_at'), '>=', $carbonDate)
                        ->where($this->getTable().'.'.$this->getSelectionOption('dates.columns.end', 'created_at'), '<=', $carbonDate->copy()->addDay());
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
            ->where($this->getTable().'.'.$this->getSelectionOption('interval.columns.begin', 'created_at'), '>=', $carbonDate1)
            ->where($this->getTable().'.'.$this->getSelectionOption('interval.columns.end', 'created_at'), '<=', $carbonDate2);
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
            ->where($this->getTable().'.'.$this->getSelectionOption('day.columns.begin', 'created_at'), '>=', $carbonDate)
            ->where($this->getTable().'.'.$this->getSelectionOption('day.columns.end', 'created_at'), '<=', $carbonDate->copy()->addDay());
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
            ->where($this->getTable().'.'.$this->getSelectionOption('week.columns.begin', 'created_at'), '>=', $carbonDate)
            ->where($this->getTable().'.'.$this->getSelectionOption('week.columns.end', 'created_at'), '<=', $carbonDate->copy()->addWeek());
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
            ->where($this->getTable().'.'.$this->getSelectionOption('month.columns.begin', 'created_at'), '>=', $carbonDate)
            ->where($this->getTable().'.'.$this->getSelectionOption('month.columns.end', 'created_at'), '<=', $carbonDate->copy()->addMonth());
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
            ->where($this->getTable().'.'.$this->getSelectionOption('year.columns.begin', 'created_at'), '>=', $carbonDate)
            ->where($this->getTable().'.'.$this->getSelectionOption('year.columns.end', 'created_at'), '<=', $carbonDate->copy()->addYear());
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
                $defaultParams = is_array($default) ? $this->getSelectionOption($selector.'.default') : $default;
                $params = \Request::input($selector) ?? $defaultParams;

                if ($selector === 'paginate' || $params === null) // Paginate returns a collection
                    continue;

                if (in_array($selector, $dateSelectors) && ($this->uniqueDateSelector ?? true)) {
                    if (!\Request::filled($selector))
                        continue;

                    if ($dateSelection)
                        throw new SelectionException('Can\'t set the selector '.$selector.' after the selector '.$dateSelection);

                    $dateSelection = $selector;
                }

                try {
                    $method = 'scope'.ucfirst($selector);

                    if (is_array($params)) {
                        foreach ($params as $key => $param) {
                            $query = $this->$method(
                                $query,
                                $key,
                                ...(is_array($param) ? $param : explode(',', $param))
                            );
                        }
                    }
                    else {
                        $query = $this->$method(
                            $query,
                            ...(is_array($params) ? $params : explode(',', $params))
                        );
                    }
                } catch (SelectionException $e) {
                    throw new SelectionException("$selector: ".$e->getMessage());
                } catch (\BadMethodCallException $e) {
                    throw new SelectionException("$selector: More parameters (spaced by `,`) are expected");
                } catch (\InvalidArgumentException $e) {
                    throw new SelectionException("$selector: Bad parameters were given");
                } catch (\Error $e) {
                    throw new SelectionException('An error occured when executing the selector '.$selector);
                } catch (\Exception $e) {
                    throw new SelectionException('An exception were detected when executing the selector '.$selector);
                }

                if (!($query instanceof Builder)) // In case where a selector returns a data directly
                    return $query;
            }

            if (in_array('paginate', array_keys($this->selection))) {
                $default = $this->selection['paginate'];
                $param = Request::input('paginate', is_array($default) ? $this->getSelectionOption('paginate.default') : $default);

                if ($param) { // Must be treated at last
                    return new Collection($this->scopePaginate(
                        $query,
                        Request::input('paginate', $this->selection['paginate'])
                    )->items());
                }
            }
        }

        return $query;
    }

    /**
     * Show all items with the different selectors defined in the model
     * @param  Builder $query
     * @return Collection|false
     */
    public function scopeGetSelection(Builder $query, bool $allowEmptySelection = false) {
        $selection = $this->scopeSelect($query);
        $collection = $selection instanceof Builder ? $selection->get() : $selection;

        if ((is_null($collection) || count($collection) === 0) && !(($this->selectionCanBeEmpty ?? false) || $allowEmptySelection))
            throw new SelectionException('The selection is maybe too constraining or the page is empty', 416);

        return $collection ?? false;
    }

    /**
     * Show the first item matching the different selectors defined in the model
     * @param  Builder $query
     * @return mixed|false
     */
    public function scopeFirstSelection(Builder $query, bool $allowEmptySelection = true) {
        $selection = $this->scopeSelect($query);
        $model = $selection instanceof Builder ? $selection->first() : ($selection->first() ?? null);

        if (is_null($model) && !(($this->selectionCanBeEmpty ?? false) || $allowEmptySelection))
            throw new SelectionException('The selection is maybe too constraining or the page is empty', 416);

        return $model ?? false;
    }

    /**
     * Show the first item matching the different selectors defined in the model
     * @param  Builder $query
     * @param  mixed   $modelId
     * @return mixed|false
     */
    public function scopeFindSelection(Builder $query, $modelId, bool $allowEmptySelection = true) {
        $selection = $this->scopeSelect($query->where($this->getKeyName(), $modelId));
        $model = ($selection->first() ?? null);

        if (is_null($model) && !(($this->selectionCanBeEmpty ?? false) || $allowEmptySelection))
            throw new SelectionException('The selection is maybe too constraining or the page is empty', 416);

        return $model ?? false;
    }
}
