<div class="space-y-6">
    <!-- Send Invitation Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ __('Manage Invitations') }}
            </h3>
            @unless($showSendForm)
                <button
                    wire:click="$set('showSendForm', true)"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                    {{ __('Send Invitation') }}
                </button>
            @endunless
        </div>

        @if($showSendForm)
            <form wire:submit="sendInvitation" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Email Address') }}
                    </label>
                    <input
                        wire:model="email"
                        type="email"
                        id="email"
                        placeholder="john@example.com"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    />
                    @error('email')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Role') }}
                    </label>
                    <select
                        wire:model="role"
                        id="role"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                        {{ __('Send') }}
                    </button>
                    <button
                        type="button"
                        wire:click="$set('showSendForm', false)"
                        class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white rounded-lg hover:bg-gray-400 transition"
                    >
                        {{ __('Cancel') }}
                    </button>
                </div>
            </form>
        @endif
    </div>

    <!-- Pending Invitations List -->
    @if($pendingInvitations->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('Pending Invitations') }}
                </h4>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                {{ __('Email') }}
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                {{ __('Role') }}
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                {{ __('Sent By') }}
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                {{ __('Expires') }}
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($pendingInvitations as $invitation)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $invitation->email }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                        {{ $invitation->getRoleEnum()->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $invitation->invitedByUser?->name ?? __('Unknown') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <span @class([
                                        'text-red-600 dark:text-red-400' => $invitation->isExpired(),
                                    ])>
                                        {{ $invitation->expires_at->format('M d, Y') }}
                                        @if($invitation->isExpired())
                                            <span class="text-red-600 dark:text-red-400 font-semibold">
                                                {{ __('(Expired)') }}
                                            </span>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm space-x-2">
                                    @if(!$invitation->isExpired())
                                        <button
                                            wire:click="resendInvitation('{{ $invitation->uuid }}')"
                                            class="text-blue-600 dark:text-blue-400 hover:underline"
                                        >
                                            {{ __('Resend') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $pendingInvitations->links() }}
            </div>
        </div>
    @endif

    <!-- User's Pending Invitations -->
    @if($userPendingInvitations->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('Invitations for You') }}
                </h4>
            </div>

            <div class="space-y-4 p-6">
                @foreach($userPendingInvitations as $invitation)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex justify-between items-center">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $invitation->organization->name }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Expires') }}: {{ $invitation->expires_at->format('M d, Y') }}
                            </p>
                        </div>
                        <div class="space-x-2">
                            <button
                                wire:click="acceptInvitation('{{ $invitation->uuid }}')"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                            >
                                {{ __('Accept') }}
                            </button>
                            <button
                                wire:click="declineInvitation('{{ $invitation->uuid }}')"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                            >
                                {{ __('Decline') }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $userPendingInvitations->links() }}
            </div>
        </div>
    @endif

    @if($pendingInvitations->count() === 0 && $userPendingInvitations->count() === 0)
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 text-center">
            <p class="text-gray-600 dark:text-gray-400">
                {{ __('No pending invitations') }}
            </p>
        </div>
    @endif
</div>
