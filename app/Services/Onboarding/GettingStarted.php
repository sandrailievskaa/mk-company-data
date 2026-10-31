<?php

namespace App\Services\Onboarding;

use App\Models\User;

/**
 * Progress of the "Започни" guide. A step is completed by really doing it in the panel
 * (opening the list, a company, creating or sending an offer), never by ticking a box.
 */
class GettingStarted
{
    /** @var list<string> */
    public const STEPS = ['browse', 'inspect', 'offer', 'send', 'track'];

    public function complete(?User $user, string $step): void
    {
        if ($user === null || ! in_array($step, self::STEPS, true)) {
            return;
        }

        $state = $this->state($user);

        if (isset($state['done'][$step])) {
            return;
        }

        $state['done'][$step] = now()->toDateTimeString();
        $this->save($user, $state);
    }

    public function isDone(?User $user, string $step): bool
    {
        return $user !== null && isset($this->state($user)['done'][$step]);
    }

    public function doneCount(?User $user): int
    {
        if ($user === null) {
            return 0;
        }

        return count(array_intersect(self::STEPS, array_keys($this->state($user)['done'])));
    }

    public function isDismissed(?User $user): bool
    {
        return $user === null || (bool) $this->state($user)['dismissed'];
    }

    public function isIntroSeen(?User $user): bool
    {
        return $user === null || (bool) $this->state($user)['intro_seen'];
    }

    public function markIntroSeen(User $user): void
    {
        $this->save($user, [...$this->state($user), 'intro_seen' => true]);
    }

    public function dismiss(User $user): void
    {
        $this->save($user, [...$this->state($user), 'dismissed' => true]);
    }

    public function restore(User $user): void
    {
        $this->save($user, [...$this->state($user), 'dismissed' => false]);
    }

    public function reset(User $user): void
    {
        $this->save($user, ['done' => [], 'dismissed' => false, 'intro_seen' => false]);
    }

    /**
     * @return array{done: array<string, string>, dismissed: bool, intro_seen: bool}
     */
    private function state(User $user): array
    {
        $raw = is_array($user->onboarding) ? $user->onboarding : [];

        return [
            'done' => is_array($raw['done'] ?? null) ? $raw['done'] : [],
            'dismissed' => (bool) ($raw['dismissed'] ?? false),
            'intro_seen' => (bool) ($raw['intro_seen'] ?? false),
        ];
    }

    /**
     * @param  array{done: array<string, string>, dismissed: bool, intro_seen: bool}  $state
     */
    private function save(User $user, array $state): void
    {
        $user->forceFill(['onboarding' => $state])->saveQuietly();
    }
}
