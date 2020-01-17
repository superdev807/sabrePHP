<?php
define("POST", "POST");

$environment='https://api-crt.cert.havail.sabre.com';
//'V1:7hnzjg6s13xt77b8:DEVCENTER:EXT';
$userId='516829';
$domain='AA';
$Group='1QWH';  
$formatVersion='V1';

$clientSecret=base64_encode('WS238716');
$client_id=base64_encode($formatVersion.":".$userId.":".$Group.":".$domain);
$bulid_credential=base64_encode($client_id.":".$clientSecret);
echo($bulid_credential);
echo("<br>");
######################## Step 1: Get Token #############################
function getToken($bulid_credential) {
    $ch =curl_init("https://api-crt.cert.havail.sabre.com/v2/auth/token");
    $vars ="grant_type=client_credentials";
    $header =array(
        'Authorization: Basic '.$bulid_credential,
        'Accept: */*',
        'Content-Type: application/x-www-form-urlencoded'
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res= curl_exec($ch);
    $result=json_decode($res);
    $token=$result->access_token;
    return $token;
}
$token = getToken($bulid_credential);
echo($token."<br/><br/>");
########################## Step 2: Call the REST API###################
$origin = "PHL";
$destination = "BOS";
$departureDate = "2019-09-15";

function buildHeaders($token) {
    $headers = array(
        'Authorization: Bearer '.$token,
        'Content-Type: application/json',
    );
    return $headers;
}

function prepareCall($token,$callType, $path, $request) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $callType);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    
    $headers = buildHeaders($token);
    switch ($callType) {
    case "GET":
        $url = $path;
        if ($request != null) {
            $url = "https://api-crt.cert.havail.sabre.com".$path.'?'.http_build_query($request);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        break;
    case "POST":
        curl_setopt($ch, CURLOPT_URL, "https://api-crt.cert.havail.sabre.com" . $path);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        break;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    return $ch;
}

function executeGetCall($token, $path, $request) {
    $result = curl_exec(prepareCall($token, "GET", $path, $request));
    return json_decode($result);
}

function executePostCall($token, $path, $request) {
    $result = curl_exec(prepareCall($token, "POST", $path, $request));
    return json_decode($result);
}

function leadCalendarPrice($origin, $destination, $departureDate) {
    $jsonrequest = array(
        "lengthofstay" => "5",
        "pointofsalecountry" => "US",
        "origin" => $origin,
        "destination" => $destination,
        "departuredate" => $departureDate,
    );
    return $jsonrequest;
}

function customrsessionToken() {
    $request = '{
        "ChainId": "516829",
        "IDContext": "CRM",
        "ProfileID": "CU78877656",
        "UserType": "Guest"
    }';
}

function getBargainFinderMax($origin, $destination, $departureDate) {
    $request = '{
        "OTA_AirLowFareSearchRQ": {
            "OriginDestinationInformation": [
                {
                    "DepartureDateTime": "'.$departureDate.'T00:00:00",
                    "DestinationLocation": {
                        "LocationCode": "'.$destination.
                    '"},
                    "OriginLocation": {
                        "LocationCode": "'.$origin.
                    '"},
                    "RPH":"1"
                }
            ],
            "POS": {
                "Source": [
                    {
                        "RequestorID": {
                            "CompanyName": {
                                "Code": "TN"
                            },
                            "ID": "1",
                            "Type": "1" 
                        }
                    }
                ]
            },
            "TPA_Extensions": {
                "IntelliSellTransaction": {
                    "RequestType": {
                        "Name": "50ITINS"
                    }
                }
            },
            "TravelerInfoSummary": {
                "AirTravelerAvail": [
                    {
                        "PassengerTypeQuantity": [
                            {
                                "Code": "ADT",
                                "Quantity": 1
                            }
                        ]
                    }
                ]
            }
        }
    }'; 
    return $request;
}

function createPNRRequest($bargain) {
/**
 * Customer Info
 * Phone Number
 * first name
 * second name
 * email-id
 */
    $PricedItinerary = $bargain->OTA_AirLowFareSearchRS->PricedItineraries->PricedItinerary[0];
    $originDestinationOption = $PricedItinerary->AirItinerary->OriginDestinationOptions->OriginDestinationOption[0];
    $flightSegment = $originDestinationOption->FlightSegment[0];
    $AirItineraryPricingInfo = $PricedItinerary->AirItineraryPricingInfo[0];
    echo var_dump(json_encode($PricedItinerary));

    $request = '{
        "CreatePassengerNameRecordRQ" : {
            "version": "2.2.0",
            "haltOnAirPriceError": false,
            "Profile" : {
                "UniqueID" : {
                    "id" : "516829"
                }
            },
            "TravelItineraryAddInfo": {
                "AgencyInfo": {
                    "Address": {
                        "AddressLine": "ADAM TRAVEL",
                        "CityName": "SOUTHLAKE",
                        "CountryCode": "US",
                        "PostalCode": "76092",
                        "StateCountyProv": {
                            "StateCode": "TX"
                        },
                        "StreetNmbr": "3150 SABRE DRIVE"
                    },
                    "Ticketing" : {
                        "TicketType": "'.$PricedItinerary->TicketingInfo->TicketType.'"
                    }
                },
                "CustomerInfo": {
                    "ContactNumbers": {
                        "ContactNumber": [
                            {
                                "NameNumber": "1.1",
                                "Phone": "917-972-9380",
                                "PhoneUseType": "H"
                            }
                        ]
                    },
                    "PersonName": [
                        {
                            "NameNumber": "1.1",
                            "NameReference": "REF1",
                            "GivenName": "Ahmed",
                            "Surname": "Onsi",
                            "PassengerType": "ADT"
                        }
                    ],
                    "Email": [
                        {
                            "NameNumber": "1.1",
                            "Address" : "onsi@hotmail.com"
                        }
                    ]
                }
            },
            "AirBook" : {
                "OriginDestinationInformation" : {
                     "FlightSegment": [
                        {
                            "ArrivalDateTime" : "'.$flightSegment->ArrivalDateTime.'",
                            "DepartureDateTime" : "'.$flightSegment->DepartureDateTime.'",
                            "FlightNumber" : "'.$flightSegment->FlightNumber.'",
                            "NumberInParty": "1",
                            "ResBookDesigCode": "'.$flightSegment->ResBookDesigCode.'",
                            "Status": "NN",
                            "DestinationLocation": {
                                "LocationCode": "'.$flightSegment->ArrivalAirport->LocationCode.'"
                            },
                            "OriginLocation": {
                                "LocationCode": "'.$flightSegment->DepartureAirport->LocationCode.'"
                            },
                            "MarriageGrp": "'.$flightSegment->MarriageGrp.'",
                            "MarketingAirline": {
                                "Code": "'.$flightSegment->MarketingAirline->Code.'",
                                "FlightNumber": "'.$flightSegment->OperatingAirline->FlightNumber.'"
                            }
                        }
                    ]
                }
            },
            "AirPrice" : [
                {
                    "PriceRequestInformation" : {
                        "Retain": true,
                        "OptionalQualifiers": {
                            "FlightQualifiers" : {
                                "VendorPrefs" : {
                                    "Airline" : {
                                        "Code" : "'.$PricedItinerary->TPA_Extensions->ValidatingCarrier->Code.'"
                                    }
                                }
                            },
                            "FOP_Qualifiers": {
                                "BasicFOP": {
                                    "Type": "CK"
                                }
                            },
                            "PricingQualifiers": {
                                "PassengerType": [
                                    {
                                        "Code": "ADT",
                                        "Quantity": "1"
                                    }
                                ]
                            }
                        }
                    }
                }
            ],
            "SpecialReqDetails": {
                "AddRemark": {
                    "RemarkInfo": {
                        "FOP_Remark": {
                            "Type": "CHECK"
                        }
                    }
                },
                "SpecialService": {
                    "SpecialServiceInfo": {
                        "SecureFlight": [
                            {
                                "SegmentNumber": "1",
                                "PersonName": {
                                    "DateOfBirth": "1971-10-01",
                                    "Gender": "M",
                                    "NameNumber": "1.1",
                                    "GivenName": "Ahmed",
                                    "Surname": "Onsi"
                                },
                                "VendorPrefs": {
                                    "Airline": {
                                        "Hosted": true
                                    }
                                }
                            }
                        ],
                        "Service": [
                            {
                                "SSR_Code": "OTHS",
                                "Text": "CC Ahmed Onsi"
                            }
                        ]
                    }
                }
            },
            "PostProcessing": {
                "ARUNK": {
                    "priorPricing" : true
                },
                "EndTransaction": {
                    "Source": {
                        "ReceivedFrom": "SWS TESTING"
                    }
                },
                "RedisplayReservation": {
                    "waitInterval": 100
                }
            }
        }
    }';

    echo "<br/>-------------<br/>";
    echo ($request);
    echo "<br/>-------------<br/>";

    return json_encode(json_decode($request));
}

function issueTicket($info , $number) {
    
    $request = '{
        "AirTicketRQ": {
          "version": "1.2.0",
          "DesignatePrinter": {
              "Printers" : {
                  "Hardcopy" : {
                    "LNIATA" : "466B8E"
                  },
                  "Ticket" : {
                      "LNIATA" : "466B8E",
                      "CountryCode" : "TG",
                      "MiniItinerary" : {
                        "LNIATA" : "466B8E"
                      }
                  }
              }
          },
          "Itinerary": {
            "ID": "'.$info->ItineraryRef->ID.'"
          },
          "Ticketing": [
            {
              "PricingQualifiers": {
                "PriceQuote": [
                {
                    "Record": [
                        {
                            "Number": '.$number.'
                        }
                    ]
                  }
                ]
              }
            }
          ],
          "PostProcessing": {
            "EndTransaction": {
              "Source": {
                "ReceivedFrom": "SP TEST"
              }
            }
          }
        }
      }';
    return $request;
}

echo "<br/><br/>LeadCalendarPrice(Optional)<br/><br/>";
$payload = leadCalendarPrice($origin, $destination, $departureDate);
$result = executeGetCall($token, '/v2/shop/flights/fares',$payload);
echo var_dump(json_encode($result));
echo "<br/><br/>";

echo "BargainFinderMax<br/><br/>";
$payload = getBargainFinderMax($origin,$destination,$departureDate);
$bargain = executePostCall($token,'/v1.8.6/shop/flights?mode=live',$payload);
echo var_dump(json_encode($bargain));
echo "<br/><br/>";

echo "<br/><br/>";
$payload = customrsessionToken();
$result = executePostCall($token,'/crm/session',$payload);
echo "customerToken<br/>";
echo var_dump(json_encode($result));
echo "<br/><br/>";

$token = getToken($bulid_credential);
echo "Create PNR";
echo "<br/><br/>";
$payload = createPNRRequest($bargain);
$result = executePostCall($token, "/v2.2.0/passenger/records?mode=create", $payload);
echo "-----------<br/>";
echo var_dump(json_encode($result));
echo "<br/><br/>";

echo "issue Ticket";
echo "<br/><br/>";
$ticketpayload = issueTicket($result->CreatePassengerNameRecordRS, 1);
$result = executePostCall($token, "/v1.2.0/air/ticket", $ticketpayload);
echo(var_dump(json_encode($result)));
?>