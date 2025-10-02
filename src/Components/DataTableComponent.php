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

    public $showSearch;
    public $searchPlaceholder;
    public $showReset;
    public $resetLabel;

    public $availableColumns = [];
    public $selectedColumns = [];
    public $booleanColumns = [];
    public $booleanColumnsState = [];

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
        $columnSlots = [],
        $showSearch = null,
        $searchPlaceholder = null,
        $showReset = null,
        $resetLabel = null,
        $availableColumns = [],
        $selectedColumns = [],
        $booleanColumns = []
    ) {
        $this->model = $model;

        $this->columns = $columns;  // legacy usage
        $this->availableColumns = $availableColumns ?: $columns;
        $this->selectedColumns = $selectedColumns ?: $columns;
        $this->booleanColumns = $booleanColumns ?? [];

        $this->filters = $filters;
        $this->theme = $theme ?? config('datatable.theme');

        $this->sortField = $sortField;
        $this->sortDirection = $sortDirection;

        $this->paginationMode = $paginationMode ?? config('datatable.pagination_mode');
        $this->perPageOptions = $perPageOptions ?? config('datatable.paginations.' . $this->paginationMode . '.per_page_options');
        $this->perPage = config('datatable.paginations.' . $this->paginationMode . '.per_page');

        $this->columnLabels = $columnLabels;
        $this->columnSlots = $columnSlots;
        $this->showSearch = $showSearch ?? config('datatable.search.show');
        $this->searchPlaceholder = $searchPlaceholder ?? config('datatable.search.placeholder');
        $this->showReset = $showReset ?? config('datatable.reset.show');
        $this->resetLabel = $resetLabel ?? config('datatable.reset.label');

        // For load more mode
        $this->limit = $this->perPage;

        $this->loadDynamicFilters();
    }

    /**
     * Load dynamic filters for the column of data table.
     *
     * @return void
     */
    public function loadDynamicFilters()
    {
        foreach ($this->filters as $col => $config) {
            // Dynamic from base/self model
            if ($config === 'self') {
                $this->filters[$col] = $this->model::query()
                    ->select($col)
                    ->distinct()
                    ->orderBy($col)
                    ->pluck($col)
                    ->filter() // remove null/empty
                    ->toArray();
            }

            // Dynamic from external model
            if (is_array($config) && isset($config['model'], $config['key'], $config['label'])) {
                $query = $config['model']::query();

                // Apply where clauses if provided
                if (isset($config['where']) && is_array($config['where'])) {
                    foreach ($config['where'] as $condition) {
                        // Supports ['column','operator','value'] or just ['column','value']
                        if (count($condition) === 3) {
                            [$column, $operator, $value] = $condition;
                            $query->where($column, $operator, $value);
                        } elseif (count($condition) === 2) {
                            [$column, $value] = $condition;
                            $query->where($column, $value);
                        }
                    }
                }

                $this->filters[$col] = $query
                    ->orderBy($config['label'])
                    ->pluck($config['label'], $config['key'])
                    ->toArray();
            }
        }
    }

    public function confirmToggle($id, $column)
    {
        $this->dispatch('confirm-toggle', id: $id, column: $column);
    }

    public function toggleBoolean($id, $column)
    {
        $config = $this->booleanColumns[$column] ?? null;
        if (!$config) return;

        $trueValue = $config['true'] ?? 1;
        $falseValue = $config['false'] ?? 0;

        $model = $this->model::findOrFail($id);

        // Toggle & save
        $model->$column = ($model->$column == $trueValue) ? $falseValue : $trueValue;
        $model->save();

        $this->booleanColumnsState[$id][$column] = $model->$column == $trueValue;

        // force re-render
        $this->resetPage();

        $this->dispatch('notify', "Updated {$column} for ID {$id}");
    }

    public function updatingPerPage($value)
    {
        $this->perPage = (int) $value;
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

    public function resetTable()
    {
        $this->reset([
            'search',
            'selectedFilters',
            'sortField',
            'sortDirection',
            'perPage',
            'limit',
        ]);

        $this->perPage = config('datatable.paginations.' . $this->paginationMode . '.per_page');
        $this->limit = $this->perPage;
        $this->sortDirection = 'asc';
        $this->resetPage();
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

        // Initialize boolean columns state
        if (!empty($this->booleanColumns)) {
            foreach ($rows as $row) {
                foreach ($this->booleanColumns as $column => $config) {
                    $trueValue = $config['true'] ?? 1;
                    $this->booleanColumnsState[$row->id][$column] = $row->$column == $trueValue;
                }
            }
        }

        // Dynamically load theme view
        return view("datatable::themes.{$this->theme}.table", [
            'table' => config('datatable.themes.' . $this->theme),
            'rows' => $rows,
        ]);
    }
}
