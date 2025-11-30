<?php

namespace App\Traits;

trait CommonQueryScopes
{
    /**
     * Scope a query to filter by date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope a query to search by title.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $title
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByTitle($query, $title)
    {
        return $query->where('title', 'like', '%' . $title . '%');
    }

    /**
     * Scope a query to filter by location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $location
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByLocation($query, $location)
    {
        return $query->where('location', 'like', '%' . $location . '%');
    }
}
