@props([
    'items' => [], // array of ['label' => '', 'value' => '']
    'cols' => 4,
])
<div class="doc-meta-band doc-meta-{{ $cols }}col">
    @foreach ($items as $item)
        @if (!empty($item['value']))
            <div class="doc-meta-cell">
                <span class="doc-meta-label">{{ $item['label'] }}</span>
                <span class="doc-meta-value">{{ $item['value'] }}</span>
            </div>
        @endif
    @endforeach
</div>
