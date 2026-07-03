@props([
    'from' => null, // array: ['title' => '', 'name' => '', 'lines' => []]
    'to' => null,
    'extra' => null, // optional 3rd column
])
<div class="doc-parties {{ $extra ? 'doc-three-col' : '' }}">
    @if ($from)
        <div class="doc-party">
            <div class="doc-party-header">{{ $from['title'] ?? 'From' }}</div>
            <div class="doc-party-body">
                @if (isset($from['name']))
                    <div class="doc-party-name">{{ $from['name'] }}</div>
                @endif
                @foreach ($from['lines'] ?? [] as $line)
                    @if ($line)
                        <div class="doc-party-detail">{{ $line }}</div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
    @if ($to)
        <div class="doc-party">
            <div class="doc-party-header">{{ $to['title'] ?? 'To' }}</div>
            <div class="doc-party-body">
                @if (isset($to['name']))
                    <div class="doc-party-name">{{ $to['name'] }}</div>
                @endif
                @foreach ($to['lines'] ?? [] as $line)
                    @if ($line)
                        <div class="doc-party-detail">{{ $line }}</div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
    @if ($extra)
        <div class="doc-party">
            <div class="doc-party-header">{{ $extra['title'] ?? 'Details' }}</div>
            <div class="doc-party-body">
                @if (isset($extra['name']))
                    <div class="doc-party-name">{{ $extra['name'] }}</div>
                @endif
                @foreach ($extra['lines'] ?? [] as $line)
                    @if ($line)
                        <div class="doc-party-detail">{{ $line }}</div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
