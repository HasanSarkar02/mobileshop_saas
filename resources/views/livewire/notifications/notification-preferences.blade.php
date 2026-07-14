<div>
    <h1 class="text-xl font-semibold mb-4">Notification Preferences</h1>
    <p class="text-sm text-gray-500 mb-4">
        Choose which channels you want for each category. In-app notifications
        always appear in your Notification Center regardless of these settings.
    </p>

    <div class="card overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="table-th text-left">Category</th>
                    @foreach ($channels as $channel)
                        <th class="table-th text-center">{{ $channel->label() }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $category)
                    <tr>
                        <td class="table-td">{{ $category->label() }}</td>
                        @foreach ($channels as $channel)
                            <td class="table-td text-center">
                                <input type="checkbox" wire:model="prefs.{{ $category->value }}.{{ $channel->value }}">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <button wire:click="save" class="btn-primary mt-4">Save preferences</button>
</div>
