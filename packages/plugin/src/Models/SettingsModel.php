<?php

namespace Solspace\Calendar\Models;

use craft\base\Model;
use Solspace\Calendar\Calendar;

class SettingsModel extends Model
{
    public const DEFAULT_DATE_OVERLAP_THRESHOLD = 5;
    public const DEFAULT_TIME_INTERVAL = 30;
    public const DEFAULT_DURATION = 60;
    public const DEFAULT_ALL_DAY = false;
    public const DEFAULT_SHOW_MINI_CAL = true;
    public const DEFAULT_SHOW_DISABLED_EVENTS = true;
    public const DEFAULT_VIEW = Calendar::VIEW_MONTH;
    public const DEFAULT_ALLOW_QUICK_CREATE = true;
    public const DEFAULT_AUTHORED_EVENT_EDIT_ONLY = false;
    public const DEFAULT_FIRST_DAY_OF_WEEK = -1;

    public const TIME_FORMAT_AUTO = 'auto';
    public const TIME_FORMAT_12_HOUR = '12-hour';
    public const TIME_FORMAT_24_HOUR = '24-hour';

    /** @var int */
    public $overlapThreshold;

    /** @var int */
    public $timeInterval;

    /** @var int */
    public $eventDuration;

    /** @var bool */
    public $allDay;

    /** @var bool */
    public $demoBannerDisabled;

    /** @var bool */
    public $showMiniCal;

    /** @var bool */
    public $showDisabledEvents;

    /** @var bool */
    public $quickCreateEnabled;

    /** @var string */
    public $defaultView;

    /** @var bool */
    public $guestAccess;

    /** @var string */
    public $descriptionFieldHandle;

    /** @var string */
    public $locationFieldHandle;

    /** @var bool */
    public $authoredEventEditOnly;

    /** @var string */
    public $pluginName;

    /** @var int */
    public $firstDayOfWeek;

    /** @var int */
    public $timeFormat;

    private static $overlapThresholds = [
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
    ];

    private static $timeIntervals = [
        5 => 5,
        10 => 10,
        15 => 15,
        30 => 30,
        60 => 60,
    ];

    private static $eventDurations = [
        30 => 30,
        60 => 60,
        120 => 120,
    ];

    /**
     * Setting default values upon construction.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->overlapThreshold = self::DEFAULT_DATE_OVERLAP_THRESHOLD;
        $this->timeInterval = self::DEFAULT_TIME_INTERVAL;
        $this->eventDuration = self::DEFAULT_DURATION;
        $this->allDay = self::DEFAULT_ALL_DAY;
        $this->demoBannerDisabled = null;
        $this->showMiniCal = self::DEFAULT_SHOW_MINI_CAL;
        $this->defaultView = self::DEFAULT_VIEW;
        $this->guestAccess = null;
        $this->quickCreateEnabled = self::DEFAULT_ALLOW_QUICK_CREATE;
        $this->showDisabledEvents = self::DEFAULT_SHOW_DISABLED_EVENTS;
        $this->authoredEventEditOnly = self::DEFAULT_AUTHORED_EVENT_EDIT_ONLY;
        $this->firstDayOfWeek = self::DEFAULT_FIRST_DAY_OF_WEEK;
        $this->timeFormat = self::TIME_FORMAT_AUTO;
    }

    public static function getOverlapThresholds(): array
    {
        return self::$overlapThresholds;
    }

    public static function getTimeIntervals(): array
    {
        return self::$timeIntervals;
    }

    public static function getEventDurations(): array
    {
        return self::$eventDurations;
    }

    public function isDemoBannerDisabled(): bool
    {
        return (bool) $this->demoBannerDisabled;
    }

    public function isMiniCalEnabled(): string
    {
        return (bool) $this->showMiniCal;
    }

    public function getFirstDayOfWeek(): int
    {
        return (int) $this->firstDayOfWeek;
    }
}
