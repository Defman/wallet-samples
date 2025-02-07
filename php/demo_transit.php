<?php
/*
 * Copyright 2022 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the 'License');
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require __DIR__ . '/vendor/autoload.php';

// Download the PHP client library from the following URL
// https://developers.google.com/wallet/generic/resources/libraries
require __DIR__ . '/lib/Walletobjects.php';

// [START setup]
// [START imports]
use Firebase\JWT\JWT;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Client as Google_Client;
// [END imports]

/** Demo class for creating and managing Transit passes in Google Wallet. */
class DemoTransit
{
  /**
   * Path to service account key file from Google Cloud Console. Environment
   * variable: GOOGLE_APPLICATION_CREDENTIALS.
   */
  public string $keyFilePath;

  /**
   * Service account credentials for Google Wallet APIs.
   */
  public ServiceAccountCredentials $credentials;

  /**
   * Google Wallet service client.
   */
  public Google_Service_Walletobjects $service;

  public function __construct()
  {
    $this->keyFilePath = getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: '/path/to/key.json';

    $this->auth();
  }
  // [END setup]

  // [START auth]
  /**
   * Create authenticated HTTP client using a service account file.
   */
  public function auth()
  {
    $scope = 'https://www.googleapis.com/auth/wallet_object.issuer';

    $this->credentials = new ServiceAccountCredentials(
      $scope,
      $this->keyFilePath
    );

    // Initialize Google Wallet API service
    $this->client = new Google_Client();
    $this->client->setApplicationName('APPLICATION_NAME');
    $this->client->setScopes($scope);
    $this->client->setAuthConfig($this->keyFilePath);

    $this->service = new Google_Service_Walletobjects($this->client);
  }
  // [END auth]

  // [START createClass]
  /**
   * Create a class.
   *
   * @param string $issuerId The issuer ID being used for this request.
   * @param string $classSuffix Developer-defined unique ID for this pass class.
   *
   * @return string The pass class ID: "{$issuerId}.{$classSuffix}"
   */
  public function createClass(string $issuerId, string $classSuffix)
  {
    // Check if the class exists
    try {
      $this->service->transitclass->get("{$issuerId}.{$classSuffix}");

      print("Class {$issuerId}.{$classSuffix} already exists!");
      return "{$issuerId}.{$classSuffix}";
    } catch (Google\Service\Exception $ex) {
      if (empty($ex->getErrors()) || $ex->getErrors()[0]['reason'] != 'classNotFound') {
        // Something else went wrong...
        print_r($ex);
        return "{$issuerId}.{$classSuffix}";
      }
    }

    // See link below for more information on required properties
    // https://developers.google.com/wallet/tickets/transit-passes/qr-code/rest/v1/transitclass
    $newClass = new Google_Service_Walletobjects_TransitClass([
      'id' => "{$issuerId}.{$classSuffix}",
      'issuerName' => 'Issuer name',
      'reviewStatus' => 'UNDER_REVIEW',
      'logo' => new Google_Service_Walletobjects_Image([
        'sourceUri' => new Google_Service_Walletobjects_ImageUri([
          'uri' => 'https://live.staticflickr.com/65535/48690277162_cd05f03f4d_o.png'
        ]),
        'contentDescription' => new Google_Service_Walletobjects_LocalizedString([
          'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
            'language' => 'en-US',
            'value' => 'Logo description'
          ])
        ])
      ]),
      'transitType' => 'BUS'
    ]);

    $response = $this->service->transitclass->insert($newClass);

    print "Class insert response\n";
    print_r($response);

    return $response->id;
  }
  // [END createClass]

  // [START updateClass]
  /**
   * Update a class.
   *
   * **Warning:** This replaces all existing class attributes!
   *
   * @param string $issuerId The issuer ID being used for this request.
   * @param string $classSuffix Developer-defined unique ID for this pass class.
   *
   * @return string The pass class ID: "{$issuerId}.{$classSuffix}"
   */
  public function updateClass(string $issuerId, string $classSuffix)
  {
    // Check if the class exists
    try {
      $updatedClass = $this->service->transitclass->get("{$issuerId}.{$classSuffix}");
    } catch (Google\Service\Exception $ex) {
      if (!empty($ex->getErrors()) && $ex->getErrors()[0]['reason'] == 'classNotFound') {
        // Class does not exist
        print("Class {$issuerId}.{$classSuffix} not found!");
        return "{$issuerId}.{$classSuffix}";
      } else {
        // Something else went wrong...
        print_r($ex);
        return "{$issuerId}.{$classSuffix}";
      }
    }

    // Update the class by adding a homepage
    $updatedClass->setHomepageUri(new Google_Service_Walletobjects_Uri([
      'uri' => 'https://developers.google.com/wallet',
      'description' => 'Homepage description'
    ]));

    // Note: reviewStatus must be 'UNDER_REVIEW' or 'DRAFT' for updates
    $updatedClass->setReviewStatus('UNDER_REVIEW');

    $response = $this->service->transitclass->update("{$issuerId}.{$classSuffix}", $updatedClass);

    print "Class update response\n";
    print_r($response);

    return $response->id;
  }
  // [END updateClass]

  // [START patchClass]
  /**
   * Patch a class.
   *
   * The PATCH method supports patch semantics.
   *
   * @param string $issuerId The issuer ID being used for this request.
   * @param string $classSuffix Developer-defined unique ID for this pass class.
   *
   * @return string The pass class ID: "{$issuerId}.{$classSuffix}"
   */
  public function patchClass(string $issuerId, string $classSuffix)
  {
    // Check if the class exists
    try {
      $this->service->transitclass->get("{$issuerId}.{$classSuffix}");
    } catch (Google\Service\Exception $ex) {
      if (!empty($ex->getErrors()) && $ex->getErrors()[0]['reason'] == 'classNotFound') {
        // Class does not exist
        print("Class {$issuerId}.{$classSuffix} not found!");
        return "{$issuerId}.{$classSuffix}";
      } else {
        // Something else went wrong...
        print_r($ex);
        return "{$issuerId}.{$classSuffix}";
      }
    }

    // Patch the class by adding a homepage
    $patchBody = new Google_Service_Walletobjects_TransitClass([
      'homepageUri' => new Google_Service_Walletobjects_Uri([
        'uri' => 'https://developers.google.com/wallet',
        'description' => 'Homepage description'
      ]),

      // Note: reviewStatus must be 'UNDER_REVIEW' or 'DRAFT' for updates
      'reviewStatus' => 'UNDER_REVIEW'
    ]);

    $response = $this->service->transitclass->patch("{$issuerId}.{$classSuffix}", $patchBody);

    print "Class patch response\n";
    print_r($response);

    return $response->id;
  }
  // [END patchClass]

  // [START addMessageClass]
  /**
   * Add a message to a pass class.
   *
   * @param string $issuerId The issuer ID being used for this request.
   * @param string $classSuffix Developer-defined unique ID for this pass class.
   * @param string $header The message header.
   * @param string $body The message body.
   *
   * @return string The pass class ID: "{$issuerId}.{$classSuffix}"
   */
  public function addClassMessage(string $issuerId, string $classSuffix, string $header, string $body)
  {
    // Check if the class exists
    try {
      $this->service->transitclass->get("{$issuerId}.{$classSuffix}");
    } catch (Google\Service\Exception $ex) {
      if (!empty($ex->getErrors()) && $ex->getErrors()[0]['reason'] == 'classNotFound') {
        // Class does not exist
        print("Class {$issuerId}.{$classSuffix} not found!");
        return "{$issuerId}.{$classSuffix}";
      } else {
        // Something else went wrong...
        print_r($ex);
        return "{$issuerId}.{$classSuffix}";
      }
    }

    $message = new Google_Service_Walletobjects_AddMessageRequest([
      'message' => new Google_Service_Walletobjects_Message([
        'header' => $header,
        'body' => $body
      ])
    ]);

    $response = $this->service->transitclass->addmessage("{$issuerId}.{$classSuffix}", $message);

    print "Class addMessage response\n";
    print_r($response);

    return $response->id;
  }
  // [END addMessageClass]

  // [START createObject]
  /**
   * Create an object.
   *
   * @param string $issuerId The issuer ID being used for this request.
   * @param string $classSuffix Developer-defined unique ID for this pass class.
   * @param string $objectSuffix Developer-defined unique ID for this pass object.
   *
   * @return string The pass object ID: "{$issuerId}.{$objectSuffix}"
   */
  public function createObject(string $issuerId, string $classSuffix, string $objectSuffix)
  {
    // Check if the object exists
    try {
      $this->service->transitobject->get("{$issuerId}.{$objectSuffix}");

      print("Object {$issuerId}.{$objectSuffix} already exists!");
      return "{$issuerId}.{$objectSuffix}";
    } catch (Google\Service\Exception $ex) {
      if (empty($ex->getErrors()) || $ex->getErrors()[0]['reason'] != 'resourceNotFound') {
        // Something else went wrong...
        print_r($ex);
        return "{$issuerId}.{$objectSuffix}";
      }
    }

    // See link below for more information on required properties
    // https://developers.google.com/wallet/tickets/transit-passes/qr-code/rest/v1/transitobject
    $newObject = new Google_Service_Walletobjects_TransitObject([
      'id' => "{$issuerId}.{$objectSuffix}",
      'classId' => "{$issuerId}.{$classSuffix}",
      'state' => 'ACTIVE',
      'heroImage' => new Google_Service_Walletobjects_Image([
        'sourceUri' => new Google_Service_Walletobjects_ImageUri([
          'uri' => 'https://farm4.staticflickr.com/3723/11177041115_6e6a3b6f49_o.jpg'
        ]),
        'contentDescription' => new Google_Service_Walletobjects_LocalizedString([
          'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
            'language' => 'en-US',
            'value' => 'Hero image description'
          ])
        ])
      ]),
      'textModulesData' => [
        new Google_Service_Walletobjects_TextModuleData([
          'header' => 'Text module header',
          'body' => 'Text module body',
          'id' => 'TEXT_MODULE_ID'
        ])
      ],
      'linksModuleData' => new Google_Service_Walletobjects_LinksModuleData([
        'uris' => [
          new Google_Service_Walletobjects_Uri([
            'uri' => 'http://maps.google.com/',
            'description' => 'Link module URI description',
            'id' => 'LINK_MODULE_URI_ID'
          ]),
          new Google_Service_Walletobjects_Uri([
            'uri' => 'tel:6505555555',
            'description' => 'Link module tel description',
            'id' => 'LINK_MODULE_TEL_ID'
          ])
        ]
      ]),
      'imageModulesData' => [
        new Google_Service_Walletobjects_ImageModuleData([
          'mainImage' => new Google_Service_Walletobjects_Image([
            'sourceUri' => new Google_Service_Walletobjects_ImageUri([
              'uri' => 'http://farm4.staticflickr.com/3738/12440799783_3dc3c20606_b.jpg'
            ]),
            'contentDescription' => new Google_Service_Walletobjects_LocalizedString([
              'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
                'language' => 'en-US',
                'value' => 'Image module description'
              ])
            ])
          ]),
          'id' => 'IMAGE_MODULE_ID'
        ])
      ],
      'barcode' => new Google_Service_Walletobjects_Barcode([
        'type' => 'QR_CODE',
        'value' => 'QR code value'
      ]),
      'locations' => [
        new Google_Service_Walletobjects_LatLongPoint([
          'latitude' => 37.424015499999996,
          'longitude' =>  -122.09259560000001
        ])
      ],
      'passengerType' => 'SINGLE_PASSENGER',
      'passengerNames' => 'Passenger names',
      'tripType' => 'ONE_WAY',
      'ticketLeg' => new Google_Service_Walletobjects_TicketLeg([
        'originStationCode' => 'LA',
        'originName' => new Google_Service_Walletobjects_LocalizedString([
          'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
            'language' => 'en-US',
            'value' => 'Origin name'
          ])
        ]),
        'destinationStationCode' => 'SFO',
        'destinationName' => new Google_Service_Walletobjects_LocalizedString([
          'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
            'language' => 'en-US',
            'value' => 'Destination name'
          ])
        ]),
        'departureDateTime' => '2020-04-12T16:20:50.52Z',
        'arrivalDateTime' => '2020-04-12T20:20:50.52Z',
        'fareName' => new Google_Service_Walletobjects_LocalizedString([
          'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
            'language' => 'en-US',
            'value' => 'Fare name'
          ])
        ])
      ])
    ]);

    $response = $this->service->transitobject->insert($newObject);

    print "Object insert response\n";
    print_r($response);

    return $response->id;
  }
  // [END createObject]

  // [START updateObject]
  /**
   * Update an object.
   *
   * **Warning:** This replaces all existing object attributes!
   *
   * @param string $issuerId The issuer ID being used for this request.
   * @param string $objectSuffix Developer-defined unique ID for this pass object.
   *
   * @return string The pass object ID: "{$issuerId}.{$objectSuffix}"
   */
  public function updateObject(string $issuerId, string $objectSuffix)
  {
    // Check if the object exists
    try {
      $updatedObject = $this->service->transitobject->get("{$issuerId}.{$objectSuffix}");
    } catch (Google\Service\Exception $ex) {
      if (!empty($ex->getErrors()) && $ex->getErrors()[0]['reason'] == 'resourceNotFound') {
        print("Object {$issuerId}.{$objectSuffix} not found!");
        return "{$issuerId}.{$objectSuffix}";
      } else {
        // Something else went wrong...
        print_r($ex);
        return "{$issuerId}.{$objectSuffix}";
      }
    }

    // Update the object by adding a link
    $newLink = new Google_Service_Walletobjects_Uri([
      'uri' => 'https://developers.google.com/wallet',
      'description' => 'New link description'
    ]);

    $linksModuleData = $updatedObject->getLinksModuleData();
    if (is_null($linksModuleData)) {
      // LinksModuleData was not set on the original object
      $linksModuleData = new Google_Service_Walletobjects_LinksModuleData([
        'uris' => []
      ]);
    }
    $uris = $linksModuleData->getUris();
    array_push(
      $uris,
      $newLink
    );
    $linksModuleData->setUris($uris);

    $updatedObject->setLinksModuleData($linksModuleData);

    $response = $this->service->transitobject->update("{$issuerId}.{$objectSuffix}", $updatedObject);

    print "Object update response\n";
    print_r($response);

    return $response->id;
  }
  // [END updateObject]

  // [START patchObject]
  /**
   * Patch an object.
   *
   * @param string $issuerId The issuer ID being used for this request.
   * @param string $objectSuffix Developer-defined unique ID for this pass object.
   *
   * @return string The pass object ID: "{$issuerId}.{$objectSuffix}"
   */
  public function patchObject(string $issuerId, string $objectSuffix)
  {
    // Check if the object exists
    try {
      $existingObject = $this->service->transitobject->get("{$issuerId}.{$objectSuffix}");
    } catch (Google\Service\Exception $ex) {
      if (!empty($ex->getErrors()) && $ex->getErrors()[0]['reason'] == 'resourceNotFound') {
        print("Object {$issuerId}.{$objectSuffix} not found!");
        return "{$issuerId}.{$objectSuffix}";
      } else {
        // Something else went wrong...
        print_r($ex);
        return "{$issuerId}.{$objectSuffix}";
      }
    }

    // Patch the object by adding a link
    $newLink = new Google_Service_Walletobjects_Uri([
      'uri' => 'https://developers.google.com/wallet',
      'description' => 'New link description'
    ]);

    $patchBody = new Google_Service_Walletobjects_TransitObject();

    $linksModuleData = $existingObject->getLinksModuleData();
    if (is_null($linksModuleData)) {
      // LinksModuleData was not set on the original object
      $linksModuleData = new Google_Service_Walletobjects_LinksModuleData([
        'uris' => []
      ]);
    }
    $uris = $linksModuleData->getUris();
    array_push(
      $uris,
      $newLink
    );
    $linksModuleData->setUris($uris);

    $patchBody->setLinksModuleData($linksModuleData);

    $response = $this->service->transitobject->patch("{$issuerId}.{$objectSuffix}", $patchBody);

    print "Object patch response\n";
    print_r($response);

    return $response->id;
  }
  // [END patchObject]

  // [START expireObject]
  /**
   * Expire an object.
   *
   * Sets the object's state to Expired. If the valid time interval is
   * already set, the pass will expire automatically up to 24 hours after.
   *
   * @param string $issuerId The issuer ID being used for this request.
   * @param string $objectSuffix Developer-defined unique ID for this pass object.
   *
   * @return string The pass object ID: "{$issuerId}.{$objectSuffix}"
   */
  public function expireObject(string $issuerId, string $objectSuffix)
  {
    // Check if the object exists
    try {
      $this->service->transitobject->get("{$issuerId}.{$objectSuffix}");
    } catch (Google\Service\Exception $ex) {
      if (!empty($ex->getErrors()) && $ex->getErrors()[0]['reason'] == 'resourceNotFound') {
        print("Object {$issuerId}.{$objectSuffix} not found!");
        return "{$issuerId}.{$objectSuffix}";
      } else {
        // Something else went wrong...
        print_r($ex);
        return "{$issuerId}.{$objectSuffix}";
      }
    }

    // Patch the object, setting the pass as expired
    $patchBody = new Google_Service_Walletobjects_TransitObject([
      'state' => 'EXPIRED'
    ]);

    $response = $this->service->transitobject->patch("{$issuerId}.{$objectSuffix}", $patchBody);

    print "Object expiration response\n";
    print_r($response);

    return $response->id;
  }
  // [END expireObject]

  // [START addMessageObject]
  /**
   * Add a message to a pass object.
   *
   * @param string $issuerId The issuer ID being used for this request.
   * @param string $objectSuffix Developer-defined unique ID for this pass object.
   * @param string $header The message header.
   * @param string $body The message body.
   *
   * @return string The pass class ID: "{$issuerId}.{$classSuffix}"
   */
  public function addObjectMessage(string $issuerId, string $objectSuffix, string $header, string $body)
  {
    // Check if the object exists
    try {
      $this->service->transitobject->get("{$issuerId}.{$objectSuffix}");
    } catch (Google\Service\Exception $ex) {
      if (!empty($ex->getErrors()) && $ex->getErrors()[0]['reason'] == 'resourceNotFound') {
        print("Object {$issuerId}.{$objectSuffix} not found!");
        return "{$issuerId}.{$objectSuffix}";
      } else {
        // Something else went wrong...
        print_r($ex);
        return "{$issuerId}.{$objectSuffix}";
      }
    }

    $message = new Google_Service_Walletobjects_AddMessageRequest([
      'message' => new Google_Service_Walletobjects_Message([
        'header' => $header,
        'body' => $body
      ])
    ]);

    $response = $this->service->transitobject->addmessage("{$issuerId}.{$objectSuffix}", $message);

    print "Object addMessage response\n";
    print_r($response);

    return $response->id;
  }
  // [END addMessageObject]

  // [START jwtNew]
  /**
   * Generate a signed JWT that creates a new pass class and object.
   *
   * When the user opens the "Add to Google Wallet" URL and saves the pass to
   * their wallet, the pass class and object defined in the JWT are
   * created. This allows you to create multiple pass classes and objects in
   * one API call when the user saves the pass to their wallet.
   *
   * @param string $issuerId The issuer ID being used for this request.
   * @param string $classSuffix Developer-defined unique ID for the pass class.
   * @param string $objectSuffix Developer-defined unique ID for the pass object.
   *
   * @return string An "Add to Google Wallet" link.
   */
  public function createJwtNewObjects(string $issuerId, string $classSuffix, string $objectSuffix)
  {
    // See link below for more information on required properties
    // https://developers.google.com/wallet/tickets/transit-passes/qr-code/rest/v1/transitclass
    $newClass = new Google_Service_Walletobjects_TransitClass([
      'id' => "{$issuerId}.{$classSuffix}",
      'issuerName' => 'Issuer name',
      'reviewStatus' => 'UNDER_REVIEW',
      'logo' => new Google_Service_Walletobjects_Image([
        'sourceUri' => new Google_Service_Walletobjects_ImageUri([
          'uri' => 'https://live.staticflickr.com/65535/48690277162_cd05f03f4d_o.png'
        ]),
        'contentDescription' => new Google_Service_Walletobjects_LocalizedString([
          'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
            'language' => 'en-US',
            'value' => 'Logo description'
          ])
        ])
      ]),
      'transitType' => 'BUS'
    ]);

    // See link below for more information on required properties
    // https://developers.google.com/wallet/tickets/transit-passes/qr-code/rest/v1/transitobject
    $newObject = new Google_Service_Walletobjects_TransitObject([
      'id' => "{$issuerId}.{$objectSuffix}",
      'classId' => "{$issuerId}.{$classSuffix}",
      'state' => 'ACTIVE',
      'heroImage' => new Google_Service_Walletobjects_Image([
        'sourceUri' => new Google_Service_Walletobjects_ImageUri([
          'uri' => 'https://farm4.staticflickr.com/3723/11177041115_6e6a3b6f49_o.jpg'
        ]),
        'contentDescription' => new Google_Service_Walletobjects_LocalizedString([
          'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
            'language' => 'en-US',
            'value' => 'Hero image description'
          ])
        ])
      ]),
      'textModulesData' => [
        new Google_Service_Walletobjects_TextModuleData([
          'header' => 'Text module header',
          'body' => 'Text module body',
          'id' => 'TEXT_MODULE_ID'
        ])
      ],
      'linksModuleData' => new Google_Service_Walletobjects_LinksModuleData([
        'uris' => [
          new Google_Service_Walletobjects_Uri([
            'uri' => 'http://maps.google.com/',
            'description' => 'Link module URI description',
            'id' => 'LINK_MODULE_URI_ID'
          ]),
          new Google_Service_Walletobjects_Uri([
            'uri' => 'tel:6505555555',
            'description' => 'Link module tel description',
            'id' => 'LINK_MODULE_TEL_ID'
          ])
        ]
      ]),
      'imageModulesData' => [
        new Google_Service_Walletobjects_ImageModuleData([
          'mainImage' => new Google_Service_Walletobjects_Image([
            'sourceUri' => new Google_Service_Walletobjects_ImageUri([
              'uri' => 'http://farm4.staticflickr.com/3738/12440799783_3dc3c20606_b.jpg'
            ]),
            'contentDescription' => new Google_Service_Walletobjects_LocalizedString([
              'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
                'language' => 'en-US',
                'value' => 'Image module description'
              ])
            ])
          ]),
          'id' => 'IMAGE_MODULE_ID'
        ])
      ],
      'barcode' => new Google_Service_Walletobjects_Barcode([
        'type' => 'QR_CODE',
        'value' => 'QR code value'
      ]),
      'locations' => [
        new Google_Service_Walletobjects_LatLongPoint([
          'latitude' => 37.424015499999996,
          'longitude' =>  -122.09259560000001
        ])
      ],
      'passengerType' => 'SINGLE_PASSENGER',
      'passengerNames' => 'Passenger names',
      'tripType' => 'ONE_WAY',
      'ticketLeg' => new Google_Service_Walletobjects_TicketLeg([
        'originStationCode' => 'LA',
        'originName' => new Google_Service_Walletobjects_LocalizedString([
          'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
            'language' => 'en-US',
            'value' => 'Origin name'
          ])
        ]),
        'destinationStationCode' => 'SFO',
        'destinationName' => new Google_Service_Walletobjects_LocalizedString([
          'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
            'language' => 'en-US',
            'value' => 'Destination name'
          ])
        ]),
        'departureDateTime' => '2020-04-12T16:20:50.52Z',
        'arrivalDateTime' => '2020-04-12T20:20:50.52Z',
        'fareName' => new Google_Service_Walletobjects_LocalizedString([
          'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
            'language' => 'en-US',
            'value' => 'Fare name'
          ])
        ])
      ])
    ]);

    // The service account credentials are used to sign the JWT
    $serviceAccount = json_decode(file_get_contents($this->keyFilePath), true);

    // Create the JWT as an array of key/value pairs
    $claims = [
      'iss' => $serviceAccount['client_email'],
      'aud' => 'google',
      'origins' => ['www.example.com'],
      'typ' => 'savetowallet',
      'payload' => [
        'transitClasses' => [
          $newClass
        ],
        'transitObjects' => [
          $newObject
        ]
      ]
    ];

    $token = JWT::encode(
      $claims,
      $serviceAccount['private_key'],
      'RS256'
    );

    print "Add to Google Wallet link\n";
    print "https://pay.google.com/gp/v/save/{$token}";

    return "https://pay.google.com/gp/v/save/{$token}";
  }
  // [END jwtNew]

  // [START jwtExisting]
  /**
   * Generate a signed JWT that references an existing pass object.
   *
   * When the user opens the "Add to Google Wallet" URL and saves the pass to
   * their wallet, the pass objects defined in the JWT are added to the
   * user's Google Wallet app. This allows the user to save multiple pass
   * objects in one API call.
   *
   * The objects to add must follow the below format:
   *
   *  {
   *    'id': 'ISSUER_ID.OBJECT_SUFFIX',
   *    'classId': 'ISSUER_ID.CLASS_SUFFIX'
   *  }
   *
   * @param string $issuerId The issuer ID being used for this request.
   *
   * @return string An "Add to Google Wallet" link.
   */
  public function createJwtExistingObjects(string $issuerId)
  {
    // Multiple pass types can be added at the same time
    // At least one type must be specified in the JWT claims
    // Note: Make sure to replace the placeholder class and object suffixes
    $objectsToAdd = [
      // Event tickets
      'eventTicketObjects' => [
        [
          'id' => "{$issuerId}.EVENT_OBJECT_SUFFIX"
        ]
      ],

      // Boarding passes
      'flightObjects' => [
        [
          'id' => "{$issuerId}.FLIGHT_OBJECT_SUFFIX"
        ]
      ],

      // Generic passes
      'genericObjects' => [
        [
          'id' => "{$issuerId}.GENERIC_OBJECT_SUFFIX"
        ]
      ],

      // Gift cards
      'giftCardObjects' => [
        [
          'id' => "{$issuerId}.GIFT_CARD_OBJECT_SUFFIX"
        ]
      ],

      // Loyalty cards
      'loyaltyObjects' => [
        [
          'id' => "{$issuerId}.LOYALTY_OBJECT_SUFFIX"
        ]
      ],

      // Offers
      'offerObjects' => [
        [
          'id' => "{$issuerId}.OFFER_OBJECT_SUFFIX"
        ]
      ],

      // Tranist passes
      'transitObjects' => [
        [
          'id' => "{$issuerId}.TRANSIT_OBJECT_SUFFIX"
        ]
      ]
    ];

    // The service account credentials are used to sign the JWT
    $serviceAccount = json_decode(file_get_contents($this->keyFilePath), true);

    // Create the JWT as an array of key/value pairs
    $claims = [
      'iss' => $serviceAccount['client_email'],
      'aud' => 'google',
      'origins' => ['www.example.com'],
      'typ' => 'savetowallet',
      'payload' => $objectsToAdd
    ];

    $token = JWT::encode(
      $claims,
      $serviceAccount['private_key'],
      'RS256'
    );

    print "Add to Google Wallet link\n";
    print "https://pay.google.com/gp/v/save/{$token}";

    return "https://pay.google.com/gp/v/save/{$token}";
  }
  // [END jwtExisting]

  // [START batch]
  /**
   * Batch create Google Wallet objects from an existing class.
   *
   * @param string $issuerId The issuer ID being used for this request.
   * @param string $classSuffix Developer-defined unique ID for the pass class.
   */
  public function batchCreateObjects(string $issuerId, string $classSuffix)
  {
    // Update the client to enable batch requests
    $this->client->setUseBatch(true);
    $batch = $this->service->createBatch();

    // Example: Generate three new pass objects
    for ($i = 0; $i < 3; $i++) {
      // Generate a random object suffix
      $objectSuffix = preg_replace('/[^\w.-]/i', '_', uniqid());

      // See link below for more information on required properties
      // https://developers.google.com/wallet/tickets/transit-passes/qr-code/rest/v1/transitobject
      $batchObject = new Google_Service_Walletobjects_TransitObject([
        'id' => "{$issuerId}.{$objectSuffix}",
        'classId' => "{$issuerId}.{$classSuffix}",
        'state' => 'ACTIVE',
        'heroImage' => new Google_Service_Walletobjects_Image([
          'sourceUri' => new Google_Service_Walletobjects_ImageUri([
            'uri' => 'https://farm4.staticflickr.com/3723/11177041115_6e6a3b6f49_o.jpg'
          ]),
          'contentDescription' => new Google_Service_Walletobjects_LocalizedString([
            'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
              'language' => 'en-US',
              'value' => 'Hero image description'
            ])
          ])
        ]),
        'textModulesData' => [
          new Google_Service_Walletobjects_TextModuleData([
            'header' => 'Text module header',
            'body' => 'Text module body',
            'id' => 'TEXT_MODULE_ID'
          ])
        ],
        'linksModuleData' => new Google_Service_Walletobjects_LinksModuleData([
          'uris' => [
            new Google_Service_Walletobjects_Uri([
              'uri' => 'http://maps.google.com/',
              'description' => 'Link module URI description',
              'id' => 'LINK_MODULE_URI_ID'
            ]),
            new Google_Service_Walletobjects_Uri([
              'uri' => 'tel:6505555555',
              'description' => 'Link module tel description',
              'id' => 'LINK_MODULE_TEL_ID'
            ])
          ]
        ]),
        'imageModulesData' => [
          new Google_Service_Walletobjects_ImageModuleData([
            'mainImage' => new Google_Service_Walletobjects_Image([
              'sourceUri' => new Google_Service_Walletobjects_ImageUri([
                'uri' => 'http://farm4.staticflickr.com/3738/12440799783_3dc3c20606_b.jpg'
              ]),
              'contentDescription' => new Google_Service_Walletobjects_LocalizedString([
                'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
                  'language' => 'en-US',
                  'value' => 'Image module description'
                ])
              ])
            ]),
            'id' => 'IMAGE_MODULE_ID'
          ])
        ],
        'barcode' => new Google_Service_Walletobjects_Barcode([
          'type' => 'QR_CODE',
          'value' => 'QR code value'
        ]),
        'locations' => [
          new Google_Service_Walletobjects_LatLongPoint([
            'latitude' => 37.424015499999996,
            'longitude' =>  -122.09259560000001
          ])
        ],
        'passengerType' => 'SINGLE_PASSENGER',
        'passengerNames' => 'Passenger names',
        'tripType' => 'ONE_WAY',
        'ticketLeg' => new Google_Service_Walletobjects_TicketLeg([
          'originStationCode' => 'LA',
          'originName' => new Google_Service_Walletobjects_LocalizedString([
            'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
              'language' => 'en-US',
              'value' => 'Origin name'
            ])
          ]),
          'destinationStationCode' => 'SFO',
          'destinationName' => new Google_Service_Walletobjects_LocalizedString([
            'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
              'language' => 'en-US',
              'value' => 'Destination name'
            ])
          ]),
          'departureDateTime' => '2020-04-12T16:20:50.52Z',
          'arrivalDateTime' => '2020-04-12T20:20:50.52Z',
          'fareName' => new Google_Service_Walletobjects_LocalizedString([
            'defaultValue' => new Google_Service_Walletobjects_TranslatedString([
              'language' => 'en-US',
              'value' => 'Fare name'
            ])
          ])
        ])
      ]);

      $batch->add($this->service->transitobject->insert($batchObject));
    }

    // Make the batch request
    $batchResponse = $batch->execute();

    print "Batch insert response\n";
    foreach ($batchResponse as $key => $value) {
      if ($value instanceof Google_Service_Exception) {
        print_r($value->getErrors());
        continue;
      }
      print "{$value->getId()}\n";
    }
  }
  // [END batch]
}
