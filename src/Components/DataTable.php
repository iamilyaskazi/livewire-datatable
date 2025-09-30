<?php

namespace IamIlyasKazi\LivewireDataTable\Components;

use Livewire\Component;
use Livewire\WithPagination;

class DataTable extends Component
{
    use WithPagination;

    public $model;
    public $columns = [];
    public $filters = [];
    public $theme;
    public $search = '';
    public $perPage;
    public $paginationMode;
    public $limit;
    public $selectedFilters = [];

    protected $queryString = ['search', 'page', 'perPage', 'selectedFilters'];
    public $sortField = null;
    public $sortDirection = 'asc';

    public function mount(
        $model,
        $columns = [],
        $filters = [],
        $theme = null,
        $sortField = null,
        $sortDirection = 'asc',
        $paginationMode = null
    ) {
        $this->model = $model;
        $this->columns = $columns;
        $this->filters = $filters;
        $this->perPage = config('datatable.per_page');
        $this->theme = $theme ?? config('datatable.theme');
        $this->sortField = $sortField;
        $this->sortDirection = $sortDirection;
        $this->paginationMode = $paginationMode ?? config('datatable.pagination_mode');

        // For load more mode
        $this->limit = $this->perPage;
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
        if ($this->paginationMode === 'load-more') {
            $rows = $query->take($this->limit)->get();
        } else {
            $rows = $query->paginate($this->perPage);
        }

        // Dynamically load theme view
        return view("datatable::themes.{$this->theme}.table", [
            'config' => config('datatable.themes.' . $this->theme),
            'rows' => $rows,
        ]);
    }
}
