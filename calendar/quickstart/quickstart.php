<?php
/**
 * Copyright 2018 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
// [START calendar_quickstart]
require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

use Google\Client;
use Google\Service\Calendar;

class Test
{
    public $client = null;

    public $calendarId = 'primary';
    // public $calendarId = 'c09b6787e5a153eaee1d2bc12b7b273fb0e5ddc359698eae53decb654c585967@group.calendar.google.com';

    /**
     * Get the API client and construct the service object.
     * Returns an authorized API client.
     * @return Client the authorized client object
     * @throws \Google\Exception
     */
    public function __construct()
    {
        $client = new Client();
        $client->setApplicationName('Google Calendar API PHP Quickstart');

        // $client->setScopes('https://www.googleapis.com/auth/calendar');
        $client->setScopes(Calendar::CALENDAR);

        $client->setAuthConfig('credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->setState('state123');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first time.
        $tokenPath = 'token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0777, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }

        $this->client = $client;

        return $client;
    }

    public function getCalendarList()
    {
        $service = new Calendar($this->client);
        $calendarList = $service->calendarList->listCalendarList();
        // printf('Calendar List: %s\n', $calendarList);

        while(true) {
            foreach ($calendarList->getItems() as $calendarListEntry) {
                echo 'title=' . $calendarListEntry->getSummary() . '; Id=' . $calendarListEntry->getId() . PHP_EOL;
            }

            $pageToken = $calendarList->getNextPageToken();
            if ($pageToken) {
                $optParams = array('pageToken' => $pageToken);
                $calendarList = $service->calendarList->listCalendarList($optParams);
            } else {
                break;
            }
        }
    }

    public function getEventList()
    {
        $service = new Calendar($this->client);

        // Print the next 10 events on the user's calendar.
        try {
            $calendarId = 'primary';
            $optParams = array(
                'maxResults' => 10,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => date('c'),
            );
            $results = $service->events->listEvents($calendarId, $optParams);
            $events = $results->getItems();

            if (empty($events)) {
                print "No upcoming events found.\n";
            } else {
                print "Upcoming events:\n";
                foreach ($events as $event) {
                    $start = $event->start->dateTime;
                    if (empty($start)) {
                        $start = $event->start->date;
                    }
                    printf("%s (%s)\n", $event->getSummary(), $start);
                }
            }
        } catch (Exception $e) {
            // TODO(developer) - handle error appropriately
            echo 'Message: ' . $e->getMessage();
        }
    }

    public function insertEvent()
    {
        $event = new Google_Service_Calendar_Event(array(
            'summary' => 'summary 01',
            'location' => '800 Howard St., San Francisco, CA 94103',
            'description' => 'description test 01',

            // all day of type,  is 'date', not 'dateTime'
            'start' => array(
                'date' => '2024-09-23',
                'timeZone' => 'UTC',
            ),
            'end' => array(
                'date' => '2024-09-23',
                'timeZone' => 'UTC',
            ),

            // 'start' => array(
            //     'dateTime' => '2024-09-27T09:00:00-12:00',
            //     'timeZone' => 'UTC',
            // ),
            // 'end' => array(
            //     'dateTime' => '2024-09-27T17:00:00-19:00',
            //     'timeZone' => 'UTC',
            // ),
            // 'recurrence' => array(
            //     'RRULE:FREQ=DAILY;COUNT=2'
            // ),
            // 'attendees' => array(
            //     array('email' => 'lpage@example.com'),
            //     array('email' => 'sbrin@example.com'),
            // ),
            // 'reminders' => array(
            //     'useDefault' => FALSE,
            //     'overrides' => array(
            //         array('method' => 'email', 'minutes' => 24 * 60),
            //         array('method' => 'popup', 'minutes' => 10),
            //     ),
            // ),
        ));

        $service = new Calendar($this->client);
        $event = $service->events->insert($this->calendarId, $event);

        printf('Event created: %s\n', $event->htmlLink);
    }

    // public function insertEventBatch()
    // {
    //     $service = new Calendar($this->client);
    //     $service = new Google_Service_Calendar($this->client);
    //
    //     $events = [];
    //     for ($i = 0; $i < 3; $i++) {
    //         $index = $i + 1;
    //         $event = new Google_Service_Calendar_Event(array(
    //             'summary' => 'Event ' . $index,
    //             'description' => 'description ' . $index,
    //             // 'start' => array(
    //             //     'dateTime' => '2024-09-20T10:00:00-07:00',
    //             //     'timeZone' => 'America/Los_Angeles',
    //             // ),
    //             // 'end' => array(
    //             //     'dateTime' => '2024-09-20T11:00:00-07:00',
    //             //     'timeZone' => 'America/Los_Angeles',
    //             // ),
    //             // all day of type,  is 'date', not 'dateTime'
    //             'start' => array(
    //                 'date' => '2024-09-2' . ($index + 3),
    //                 'timeZone' => 'UTC',
    //             ),
    //             'end' => array(
    //                 'date' => '2024-09-2' . ($index + 3),
    //                 'timeZone' => 'UTC',
    //             ),
    //         ));
    //         $events[] = $event;
    //     }
    //
    //     $batch = new Google_Http_Batch($this->client);
    //     foreach ($events as $event) {
    //         $request = $service->events->insert('primary', $event);
    //         $batch->add($request);
    //     }
    //     $batch->execute();
    // }
}

$test = new Test();

$test->getCalendarList();
// $test->getEventList();
$test->insertEvent();
// $test->insertEventBatch();