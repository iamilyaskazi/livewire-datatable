<?php

namespace IamIlyasKazi\LivewireDataTable\Components;

use Livewire\Component;
use Livewire\WithPagination;
use IamIlyasKazi\LivewireDataTable\Traits\WithColumnFormatter;

class DataTableComponent extends Component
{
    use WithPagination, WithColumnFormatter;

    public $model;
    public $columns = [];
    public $filters = [];
    public $search = '';
    public $perPage;
    public $perPageOptions = [];
    public $selectedFilters = [];
    public $theme;
    public $paginationMode;
    public $limit;
    public $sortField;
    public $sortDirection = 'asc';
    public $columnLabels = [];
    public $columnSlots = [];   

    public function mount(
        $model,
        $columns = [],
        $filters = [],
        $theme = null,
        $sortField = null,
        $sortDirection = 'asc',
        $paginationMode = null,
        $perPageOptions = null,
        $columnLabels = [],
        $columnSlots = []
    ) {
        $this->model = $model;
        $this->columns = $columns;
        $this->filters = $filters;
        $this->perPage = config('datatable.per_page');
        $this->theme = $theme ?? config('datatable.theme');
        $this->sortField = $sortField;
        $this->sortDirection = $sortDirection;
        $this->paginationMode = $paginationMode ?? config('datatable.pagination_mode');
        $this->perPageOptions = $perPageOptions ?? config('datatable.per_page_options');
        $this->columnLabels = $columnLabels;
        $this->columnSlots = $columnSlots;

        // For load more mode
        $this->limit = $this->perPage;
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedFilters()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function hasSlot($col): bool
    {
        return isset($this->columnSlots[$col]);
    }

    public function getSlot($col)
    {
        return $this->columnSlots[$col] ?? null;
    }

    public function getColumnLabel($col)
    {
        return $this->columnLabels[$col] ?? ucfirst(str_replace('_', ' ', $col));
    }

    public function loadMore()
    {
        $this->limit += $this->perPage;
    }

    public function render()
    {
        $query = $this->model::query();

        // Apply search
        if ($this->search && count($this->columns)) {
            $query->where(function ($q) {
                foreach ($this->columns as $column) {
                    $q->orWhere($column, 'like', "%{$this->search}%");
                }
            });
        }

        // Apply filters
        foreach ($this->selectedFilters as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }

        // Apply sorting
        if ($this->sortField) {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        // Apply pagination or load more
        $rows = $this->paginationMode === 'load-more'
            ? $query->take($this->limit)->get()
            : $query->paginate($this->perPage);


        // Dynamically load theme view
        return view("datatable::themes.{$this->theme}.table", [
            'config' => config('datatable.themes.' . $this->theme),
            'rows' => $rows,
        ]);
    }
}
