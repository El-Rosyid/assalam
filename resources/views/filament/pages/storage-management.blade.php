<x-filament-panels::page>
    {{-- Storage Statistics --}}
    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-4">📊 Storage Usage</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($storageStats as $stat)
                <x-filament::card>
                    <div class="text-center">
                        <div class="text-2xl font-bold {{ $stat['label'] === 'Total' ? 'text-primary-600' : '' }}">
                            {{ $stat['size'] }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $stat['label'] }}
                        </div>
                    </div>
                </x-filament::card>
            @endforeach
        </div>
    </div>

    {{-- Student Stats & Trash Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <x-filament::card>
            <h3 class="text-lg font-semibold mb-4">👥 Student Data</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span>Active Students:</span>
                    <strong class="text-success-600">{{ $studentStats['active'] }}</strong>
                </div>
                <div class="flex justify-between">
                    <span>In Recycle Bin:</span>
                    <strong class="text-warning-600">{{ $studentStats['trashed'] }}</strong>
                </div>
                <div class="flex justify-between border-t pt-2">
                    <span>Total:</span>
                    <strong>{{ $studentStats['total'] }}</strong>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <h3 class="text-lg font-semibold mb-4">🗑️ .trash Folder</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span>Total Files:</span>
                    <strong>{{ $trashStats['total_files'] }}</strong>
                </div>
                <div class="flex justify-between">
                    <span>Total Size:</span>
                    <strong>{{ $trashStats['total_size'] }}</strong>
                </div>
                <div class="flex justify-between">
                    <span>Folders:</span>
                    <strong>{{ $trashStats['folders_count'] }}</strong>
                </div>
                @if($trashStats['oldest_date'])
                <div class="flex justify-between text-xs text-gray-500">
                    <span>Oldest:</span>
                    <span>{{ $trashStats['oldest_date'] }}</span>
                </div>
                @endif
            </div>
        </x-filament::card>
    </div>

    {{-- Scheduled Tasks --}}
    <x-filament::card class="mb-6">
        <h3 class="text-lg font-semibold mb-4">📅 Scheduled Tasks</h3>
        <div class="space-y-3">
            @foreach($scheduledTasks as $task)
                <div class="flex items-center justify-between border-b pb-2 last:border-0">
                    <div>
                        <div class="font-medium">{{ $task['name'] }}</div>
                        <div class="text-sm text-gray-500">{{ $task['description'] }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-mono text-gray-700 dark:text-gray-300">
                            {{ $task['schedule'] }}
                        </div>
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-success-100 text-success-700">
                            {{ ucfirst($task['status']) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::card>

    {{-- Trashed Students --}}
    <x-filament::card>
        <h3 class="text-lg font-semibold mb-4">🗑️ Recycle Bin (Last 10)</h3>
        
        @if(count($trashedStudents) === 0)
            <div class="text-center text-gray-500 py-8">
                <p>No students in recycle bin.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr class="text-left">
                            <th class="pb-2">NIS</th>
                            <th class="pb-2">Name</th>
                            <th class="pb-2">Deleted</th>
                            <th class="pb-2">Auto-Delete In</th>
                            <th class="pb-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trashedStudents as $student)
                            <tr class="border-b last:border-0">
                                <td class="py-3 font-mono text-xs">{{ $student['nis'] }}</td>
                                <td class="py-3">{{ $student['nama'] }}</td>
                                <td class="py-3 text-gray-600">{{ $student['deleted_days'] }} days ago</td>
                                <td class="py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $student['auto_delete_in'] <= 7 ? 'bg-danger-100 text-danger-700' : 'bg-warning-100 text-warning-700' }}">
                                        {{ $student['auto_delete_in'] }} days
                                    </span>
                                </td>
                                <td class="py-3 text-right space-x-2">
                                    <button 
                                        wire:click="restoreStudent('{{ $student['nis'] }}')"
                                        class="text-success-600 hover:text-success-700 font-medium text-xs">
                                        🔄 Restore
                                    </button>
                                    <button 
                                        wire:click="forceDeleteStudent('{{ $student['nis'] }}')"
                                        wire:confirm="⚠️ This will permanently delete the student and ALL files!"
                                        class="text-danger-600 hover:text-danger-700 font-medium text-xs">
                                        🗑️ Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::card>
</x-filament-panels::page>

