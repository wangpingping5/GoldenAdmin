<?php

namespace App\Listeners;

use App\Activity;
use App\Events\Settings\Updated as SettingsUpdated;
use App\Events\User\Banned;
use App\Events\User\ChangedAvatar;
use App\Events\User\Created;
use App\Events\User\Deleted;
use App\Events\User\LoggedIn;
use App\Events\User\IrLoggedIn;
use App\Events\User\LoggedOut;
use App\Events\User\Registered;
use App\Events\User\UpdatedByAdmin;
use App\Events\User\TerminatedByAdmin;
use App\Events\User\UpdatedProfileDetails;
use App\Events\User\UserEventContract;
use App\Services\Logging\UserActivity\Logger;

class UserEventsSubscriber
{
    /**
     * @var UserActivityLogger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function onLogin(LoggedIn $event)
    {
        $this->logger->log(trans('Login'));
    }

    public function onIrLogin(IrLoggedIn $event)
    {
        $this->logger->log(trans('log.irlogged_in'));
    }

    public function onLogout(LoggedOut $event)
    {
        $this->logger->log(trans('Logout'));
    }

    public function onRegister(Registered $event)
    {
        $this->logger->setUser($event->getRegisteredUser());
        $this->logger->log(trans('log.created_account'));
    }

    public function onAvatarChange(ChangedAvatar $event)
    {
        $this->logger->log(trans('log.updated_avatar'));
    }

    public function onProfileDetailsUpdate(UpdatedProfileDetails $event)
    {
        $this->logger->log(trans('log.updated_profile'));
    }

    public function onDelete(Deleted $event)
    {
        $message = trans(
            'log.deleted_user',
            ['name' => $event->getDeletedUser()->present()->username]
        );

        $this->logger->log($message);
    }

    public function onBan(Banned $event)
    {
        $message = trans(
            'log.banned_user',
            ['name' => $event->getBannedUser()->present()->username]
        );

        $this->logger->log($message);
    }

    public function onUpdateByAdmin(UpdatedByAdmin $event)
    {
        $message = trans(
            'log.updated_profile_details_for',
            ['name' => $event->getUpdatedUser()->present()->username . '/' . $event->getMention()]
        );

        $this->logger->log($message);
    }

    public function onTermiatedByAdmin(TerminatedByAdmin $event)
    {
        $this->logger->setUser($event->getTerminatedUser());

        $message = trans(
            'log.terminated_game_by',
            ['name' => auth()->user()->username]
        );

        $this->logger->log($message);
    }

    public function onCreate(Created $event)
    {
        $message = trans(
            'log.created_account_for',
            ['name' => $event->getCreatedUser()->present()->username]
        );

        $this->logger->log($message);
    }

    public function onSettingsUpdate(SettingsUpdated $event)
    {
        $this->logger->log(trans('log.updated_settings'));
    }


    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $class = 'App\Listeners\UserEventsSubscriber';

        $events->listen(LoggedIn::class, "{$class}@onLogin");
        $events->listen(IrLoggedIn::class, "{$class}@onIrLogin");
        $events->listen(LoggedOut::class, "{$class}@onLogout");
        $events->listen(Registered::class, "{$class}@onRegister");
        $events->listen(Created::class, "{$class}@onCreate");
        $events->listen(ChangedAvatar::class, "{$class}@onAvatarChange");
        $events->listen(UpdatedProfileDetails::class, "{$class}@onProfileDetailsUpdate");
        $events->listen(UpdatedByAdmin::class, "{$class}@onUpdateByAdmin");
        $events->listen(Deleted::class, "{$class}@onDelete");
        $events->listen(Banned::class, "{$class}@onBan");
        $events->listen(SettingsUpdated::class, "{$class}@onSettingsUpdate");
        $events->listen(TerminatedByAdmin::class, "{$class}@onTermiatedByAdmin");
    }
}
