<div>
    <!-- Search -->
    <div class="mb-3">
        <input type="text" wire:model.debounce.500ms="search" class="form-control" placeholder="Search...">
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
                        {{ ucfirst($col) }}
                        @if($sortField === $col)
                            {!! $sortDirection === 'asc' ? '▲' : '▼' !!}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $col)
                        <td>{{ $row->$col }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center">
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
