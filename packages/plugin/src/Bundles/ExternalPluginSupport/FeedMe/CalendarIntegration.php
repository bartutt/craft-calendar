<?php

namespace Solspace\Calendar\Bundles\ExternalPluginSupport\FeedMe;

use Cake\Utility\Hash;
use Carbon\Carbon;
use Craft;
use craft\elements\User as UserElement;
use craft\feedme\base\Element;
use craft\feedme\events\FeedProcessEvent;
use craft\feedme\Plugin;
use craft\feedme\services\Process;
use RRule\RfcParser;
use Solspace\Calendar\Calendar;
use Solspace\Calendar\Elements\Event as CalendarEvent;
use Solspace\Calendar\Elements\Event as EventElement;
use Solspace\Calendar\Library\DateHelper;
use yii\base\Event;

if (class_exists('craft\feedme\base\Element')) {
    class CalendarIntegration extends Element
    {
        const RRULE_MAP = [
            'BYMONTH' => 'byMonth',
            'BYYEARDAY' => 'byYearDay',
            'BYMONTHDAY' => 'byMonthDay',
            'BYDAY' => 'byDay',
            'UNTIL' => 'until',
            'INTERVAL' => 'interval',
            'FREQ' => 'freq',
            'COUNT' => 'count',
        ];

        public static $name = 'Solspace Calendar Event (official)';

        public static $class = CalendarEvent::class;

        public $element;

        private $rruleInfo = [];

        private $selectDates = [];

        public function getGroupsTemplate(): string
        {
            return 'feed-me/_includes/elements/calendar-events/groups';
        }

        public function getColumnTemplate(): string
        {
            return 'feed-me/_includes/elements/calendar-events/column';
        }

        public function getMappingTemplate(): string
        {
            return 'feed-me/_includes/elements/calendar-events/map';
        }

        public function init()
        {
            parent::init();

            Event::on(
                Process::class,
                Process::EVENT_STEP_BEFORE_ELEMENT_SAVE,
                function (FeedProcessEvent $event) {
                    if (EventElement::class === $event->feed['elementType']) {
                        $this->_onBeforeElementSave($event);
                    }
                }
            );

            Event::on(
                Process::class,
                Process::EVENT_STEP_AFTER_ELEMENT_SAVE,
                function (FeedProcessEvent $event) {
                    if (EventElement::class === $event->feed['elementType']) {
                        $this->_onAfterElementSave($event);
                    }
                }
            );
        }

        public function getGroups()
        {
            if (Calendar::getInstance()) {
                return Calendar::getInstance()->calendars->getAllAllowedCalendars();
            }
        }

        public function getQuery($settings, $params = [])
        {
            $query = EventElement::find()
                ->anyStatus()
                ->setCalendarId($settings['elementGroup'][EventElement::class])
                ->siteId(Hash::get($settings, 'siteId') ?: Craft::$app->getSites()->getPrimarySite()->id)
            ;
            Craft::configure($query, $params);

            return $query;
        }

        public function setModel($settings)
        {
            $siteId = (int) Hash::get($settings, 'siteId');
            $calendarId = $settings['elementGroup'][EventElement::class];

            $this->element = EventElement::create($siteId, $calendarId);

            return $this->element;
        }

        protected function parseStartDate($feedData, $fieldInfo): Carbon
        {
            return $this->_parseDate($feedData, $fieldInfo);
        }

        protected function parseEndDate($feedData, $fieldInfo)
        {
            return $this->_parseDate($feedData, $fieldInfo);
        }

        protected function parseUntil($feedData, $fieldInfo)
        {
            return $this->_parseDate($feedData, $fieldInfo);
        }

        protected function parseAuthorId($feedData, $fieldInfo)
        {
            $value = $this->fetchSimpleValue($feedData, $fieldInfo);
            $match = Hash::get($fieldInfo, 'options.match');
            $create = Hash::get($fieldInfo, 'options.create');

            // Element lookups must have a value to match against
            if (null === $value || '' === $value) {
                return null;
            }

            if (\is_array($value)) {
                $value = $value[0];
            }

            if ('fullName' === $match) {
                $element = UserElement::findOne(['search' => $value, 'status' => null]);
            } else {
                $element = UserElement::find()->status(null)->andWhere(['=', $match, $value])->one();
            }

            if ($element) {
                return $element->id;
            }

            // Check if we should create the element. But only if email is provided (for the moment)
            if ($create && 'email' === $match) {
                $element = new UserElement();
                $element->username = $value;
                $element->email = $value;

                if (!Craft::$app->getElements()->saveElement($element)) {
                    Plugin::error(
                        'Event error: Could not create author - `{e}`.',
                        ['e' => json_encode($element->getErrors())]
                    );
                } else {
                    Plugin::info('Author `#{id}` added.', ['id' => $element->id]);
                }

                return $element->id;
            }

            return null;
        }

        protected function parseRrule($feedData, $fieldInfo)
        {
            $value = $this->fetchSimpleValue($feedData, $fieldInfo);

            try {
                $rules = RfcParser::parseRRule($value);

                foreach ($rules as $ruleKey => $ruleValue) {
                    if (!\array_key_exists($ruleKey, self::RRULE_MAP)) {
                        continue;
                    }

                    $attribute = self::RRULE_MAP[$ruleKey];
                    if ('UNTIL' === $ruleKey) {
                        $ruleValue = new Carbon($ruleValue->format('Y-m-d H:i:s'), DateHelper::UTC);
                    }

                    // We can't modify other attributes here, so store them until we can
                    $this->rruleInfo[$attribute] = $ruleValue;
                }
            } catch (\Throwable $e) {
                Plugin::error($e->getMessage());
            }
        }

        protected function parseSelectDates($feedData, $fieldInfo)
        {
            $value = $this->fetchArrayValue($feedData, $fieldInfo);
            $this->selectDates = $value;
        }

        private function _parseDate($feedData, $fieldInfo)
        {
            $value = $this->fetchSimpleValue($feedData, $fieldInfo);
            $formatting = Hash::get($fieldInfo, 'options.match');

            $date = $this->parseDateAttribute($value, $formatting);

            // Calendar expects dates as Carbon object, not DateTime
            if ($date) {
                return new Carbon($date->format('Y-m-d H:i:s') ?? 'now', DateHelper::UTC);
            }

            return null;
        }

        private function _onBeforeElementSave($event)
        {
            // We prepare rrule info earlier on
            foreach ($this->rruleInfo as $key => $value) {
                $event->element->{$key} = $value;

                // Also update it in our debug info
                $event->contentData[$key] = $value;
            }
        }

        private function _onAfterElementSave($event)
        {
            if (\count($this->selectDates)) {
                $EventElement = EventElement::find()->id($event->element->id)->one();
                Calendar::getInstance()->selectDates->saveDates($EventElement, $this->selectDates);
            }
        }
    }
} else {
    class CalendarIntegration
    {
    }
}
