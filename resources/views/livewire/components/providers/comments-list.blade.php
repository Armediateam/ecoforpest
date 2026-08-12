<div class="space-y-6 p-4 md:p-6">
    @if($comments->isNotEmpty())
        @foreach($comments as $comment)
            <div class="flex items-start space-x-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex-shrink-0">
                    @if($comment->user && $comment->user->avatar)
                        <img class="h-10 w-10 rounded-full object-cover" src="{{ Storage::url($comment->user->avatar) }}" alt="{{ $comment->user->name }}">
                    @else
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-700">
                            <span class="font-medium leading-none text-slate-600 dark:text-slate-300">
                                {{ $comment->user ? strtoupper(substr($comment->user->name, 0, 2)) : '??' }}
                            </span>
                        </span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                            {{ $comment->user?->name ?? 'Unknown User' }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400" title="{{ $comment->created_at->format('Y-m-d H:i:s') }}">
                            {{ $comment->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <div class="prose prose-sm dark:prose-invert mt-1 text-slate-700 dark:text-slate-300 max-w-none">
                        {!! $comment->content !!}
                    </div>
                    {{-- You can add actions like edit/delete here if needed, based on policies --}}
                </div>
            </div>
        @endforeach
    @else
        <div class="text-center py-12">
             <div class="mx-auto w-12 h-12 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center">
                <x-filament::icon
                    icon="heroicon-o-chat-bubble-left-ellipsis"
                    class="w-6 h-6 text-slate-400 dark:text-slate-500"
                />
            </div>
            <h3 class="mt-4 text-lg font-semibold text-slate-900 dark:text-slate-100">No Comments Yet</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Be the first to add a comment to this proposal.
            </p>
            @if($record->allow_comments)
            <div class="mt-6">
                {{-- This button will trigger the existing 'add_comment' header action --}}
                <x-filament::button 
                    wire:click="$dispatch('open-modal', { id: 'add_comment' })" 
                    wire:target="$dispatch('open-modal', { id: 'add_comment' })" {{-- Add this line --}}
                    icon="heroicon-o-plus-circle">
                    Add Comment
                </x-filament::button>
            </div>
            @endif
        </div>
    @endif
</div>