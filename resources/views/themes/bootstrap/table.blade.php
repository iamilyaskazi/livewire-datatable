<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Search -->
        <input type="text" wire:model.debounce.500ms="search" class="form-control w-25" placeholder="Search...">

        <!-- Per Page Options -->
        @if($paginationMode === 'pagination')
            <div>
                <select wire:model="perPage" class="form-select">
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }} per page</option>
                    @endforeach
                </select>
            </div>
        @endif

        <!-- Reset Button -->
        <button wire:click="resetTable" type="button" class="btn btn-outline-secondary">
            Reset
        </button>
    </div>

    <!-- Filters -->
    <div class="mb-3 d-flex gap-2">
        @foreach($filters as $key => $options)
            <select wire:model="selectedFilters.{{ $key }}" class="form-select">
                <option value="">All {{ ucfirst($key) }}</option>
                @foreach($options as $option)
                    <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        @endforeach
    </div>

    <!-- Table -->
    <table class="{{ $config['class'] }}">
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th wire:click="sortBy('{{ $col }}')" style="cursor:pointer;">
                        {{ $this->getColumnLabel($col) }}
                        @if($sortField === $col)
                            {!! $sortDirection === 'asc' ? '▲' : '▼' !!}
                        @endif
                    </th>
                @endforeach
                @if($this->hasSlot('actions'))
                    <th>Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $col)
                        <td>
                            @if($this->hasSlot($col))
                                {{ $this->getSlot($col)($row) }}
                            @else
                                {!! $this->renderColumn($col, $row) ?? $this->defaultColumnRender($col, $row) !!}
                            @endif
                        </td>
                    @endforeach
                    @if($this->hasSlot('actions'))
                        <td>
                            {{ $this->getSlot('actions')($row) }}
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + ($this->hasSlot('actions') ? 1 : 0) }}" class="text-center">
                        No results found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pagination or Load More -->
    <div class="mt-3 text-center">
        @if($paginationMode === 'load-more')
            @if($rows->count() >= $limit)
                <button wire:click="loadMore" class="btn btn-secondary">
                    Load More
                </button>
            @endif
        @else
            {{ $rows->links() }}
        @endif
    </div>
</div>