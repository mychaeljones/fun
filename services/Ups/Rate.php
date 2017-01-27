<?php

//require_once 'RateWS.wsdl';

class Application_Service_Ups_Rate {

    protected $_operation = "ProcessRate";
    public $rates = array();

    public function __construct($data = array()) {
        try {
            $config = new Zend_Config_Ini(CONFIGFILE, APPLICATION_ENV, true);
            $auth = $config->toArray();
            $mode = array(
                'soap_version' => 'SOAP_1_1',
                'trace' => 1
            );
            $client = new SoapClient(APPLICATION_PATH . '/services/Ups/RateWS.wsdl', $mode);

            //create soap header
            $usernameToken['Username'] = $auth['userId'];
            $usernameToken['Password'] = $auth['passWord'];
            $serviceAccessLicense['AccessLicenseNumber'] = $auth['access'];

            $upss['UsernameToken'] = $usernameToken;
            $upss['ServiceAccessToken'] = $serviceAccessLicense;

            $header = new SoapHeader('http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0', 'UPSSecurity', $upss);
            $client->__setSoapHeaders($header);

            //get response
            $resp = $client->__soapCall("ProcessRate", array(self::processRate($data)));

           // echo '<pre>';
            //var_dump($resp);
            if ($resp->Response->ResponseStatus->Code == 1) {
                //var_dump($resp->RatedShipment);
                $serviceCode = $this->service_code();
                $index = 0;
                foreach ($resp->RatedShipment as $rate) {
                    //var_dump($rate);
                    /* echo $serviceCode["{$rate->Service->Code}"]; echo "--";
                      echo $rate->TotalCharges->CurrencyCode;echo "--";
                      echo $rate->TotalCharges->MonetaryValue;echo "--";
                      echo "<br>"; */
                    $this->rates[$index] = array('service' => $serviceCode["{$rate->Service->Code}"],
                        'rate' => $rate->TotalCharges->MonetaryValue 
                        );
                    ++$index;
                }
            }
            $this->_log_file = $auth['upsLogPath'] . date("Ymd") . '.xml';
            file_put_contents($this->_log_file, "Request: \n" . $client->__getLastRequest() . "\n", FILE_APPEND);
            file_put_contents($this->_log_file, "Response: \n" . $client->__getLastResponse() . "\n", FILE_APPEND);
        } catch (Exception $e) {
            //print_r($e->detail->Errors->ErrorDetail->PrimaryErrorCode->Description);
            $this->rates = $e->detail->Errors->ErrorDetail->PrimaryErrorCode->Description;
        }
    }

   /* function get_rates() {
        return $this->rates;
    }*/

    function processRate($data) {
        //create soap request
        $option['RequestOption'] = 'Shop';
        $request['Request'] = $option;

        $pickuptype = array('Code' => '01', 'Description' => 'Daily Pickup');
        $request['PickupType'] = $pickuptype;

        //daily rates
        $customerclassification = array('Code' => '01', 'Description' => 'Classfication');
        $request['CustomerClassification'] = $customerclassification;


        $shipper = array('Name' => 'Beaming White, LLC',
            'ShipperNumber' => '2W8306');
        $address['AddressLine'] = array(
            '1205 NE 95TH ST',
            'STE A',
            ''
        );
        $address['City'] = 'Vancouver';
        $address['StateProvinceCode'] = 'WA';
        $address['PostalCode'] = '98665';
        $address['CountryCode'] = 'US';
        $shipper['Address'] = $address;
        $shipment['Shipper'] = $shipper;
       
        $shipment['ShipTo'] = $data['shipTo'];
        $shipfrom['Name'] = 'Beaming White, LLC';
        $addressFrom['AddressLine'] = array
            (
            '1205 NE 95TH ST',
            'STE A',
            ''
        );
        $addressFrom['City'] = 'Vancouver';
        $addressFrom['StateProvinceCode'] = 'WA';
        $addressFrom['PostalCode'] = '98665';
        $addressFrom['CountryCode'] = 'US';
        $shipfrom['Address'] = $addressFrom;
        $shipment['ShipFrom'] = $shipfrom;

        //03 is ground
        /* $service['Code'] = '03';
          $service['Description'] = 'Service Code';
          $shipment['Service'] = $service;
         */
        //02 is package/customer supplied
        $packaging1['Code'] = '02';
        $packaging1['Description'] = 'Rate';
        $package1['PackagingType'] = $packaging1;
        
        $index = 0;
        foreach ($data['package'] as $package) {
            $packaging1['Code'] = '02';
            $packaging1['Description'] = 'Rate';             
            $shipment['Package'][$index]['PackagingType'] = $packaging1;
             
            $dimensions['Length'] = $package['Length'];
            $dimensions['Width'] =  $package['Width'];
            $dimensions['Height'] = $package['Height']; 
            $dunit1['Code'] = 'IN';
            $dunit1['Description'] = 'inches';        
            $dimensions['UnitOfMeasurement'] = $dunit1;
            $shipment['Package'][$index]['Dimensions'] = $dimensions;
            
            $punit1['Code'] = 'LBS';
            $punit1['Description'] = 'Pounds';
            $packageweight['Weight'] = $package['Weight'];
            $packageweight['UnitOfMeasurement'] = $punit1;
            $shipment['Package'][$index]['PackageWeight'] = $packageweight;
            ++$index;
        
        }
      //  echo '<pre>';
       // var_dump( $shipment['Package']);
        //die();
        
        
        /*$dimensions1['Length'] = $data['package'][0]['Length'];
        $dimensions1['Width'] =  $data['package'][0]['Width'];
        $dimensions1['Height'] = $data['package'][0]['Height'];
        
        $dunit1['Code'] = 'IN';
        $dunit1['Description'] = 'inches';        
        $dimensions1['UnitOfMeasurement'] = $dunit1;
        $package1['Dimensions'] = $dimensions1;
        
        $punit1['Code'] = 'LBS';
        $punit1['Description'] = 'Pounds';
        $packageweight1['Weight'] = $data['package'][0]['Weight'];
        $packageweight1['UnitOfMeasurement'] = $punit1;
        $package1['PackageWeight'] = $packageweight1;
*/
       //   echo '<pre>';
        //   var_dump($shipto);
        //    var_dump($dimensions1);
        // var_dump($package1);
        //die();


        /*  $packaging2['Code'] = '02';
          $packaging2['Description'] = 'Rate';
          $package2['PackagingType'] = $packaging2;
          $dunit2['Code'] = 'IN';
          $dunit2['Description'] = 'inches';
          $dimensions2['Length'] = '3';
          $dimensions2['Width'] = '5';
          $dimensions2['Height'] = '8';
          $dimensions2['UnitOfMeasurement'] = $dunit2;
          $package2['Dimensions'] = $dimensions2;
          $punit2['Code'] = 'LBS';
          $punit2['Description'] = 'Pounds';
          $packageweight2['Weight'] = '2';
          $packageweight2['UnitOfMeasurement'] = $punit2;
          $package2['PackageWeight'] = $packageweight2; */

        //$shipment['Package'] = array(	$package1 , $package2 );
        //$shipment['Package'] = array($package1);
        
        //var_dump($shipment['Package']);
      //  die();

        
        
        $shipment['ShipmentServiceOptions'] = '';
        $shipment['ShipmentRatingOptions'] = array('NegotiatedRatesIndicator' => '');

        $shipment['LargePackageIndicator'] = '';
        $request['Shipment'] = $shipment;
        // echo "Request.......\n";
        // print_r($request);
        //echo "\n\n";
        return $request;
    }

    function service_code() {
        return array(
            '01' => 'UPS Next Day Air',
            '02' => 'UPS Second Day Air',
            '03' => 'UPS Ground',
            '07' => 'UPS Express',
            '08' => 'UPS Expedited',
            '11' => 'UPS Standard',
            '12' => 'UPS Third Day Select',
            '13' => 'UPS Next Day Air Saver',
            '14' => 'UPS Next Day Air Early A.M.',
//'15' => 'Next Day Air Early A.M.',
            '22' => 'Ground - Returns Plus - Three Pickup Attempts',
            '32' => 'Next Day Air Early A.M. - COD',
            '33' => 'Next Day Air Early A.M. - Saturday Delivery, COD',
            '41' => 'Next Day Air Early A.M. - Saturday Delivery',
            '42' => 'Ground - Signature Required',
            '44' => 'Next Day Air - Saturday Delivery',
            '54' => 'Express Plus',
            '59' => 'Second Day Air A.M.',
            '65' => 'WorldWide Saver',
            '66' => 'Worldwide Express',
            '72' => 'Ground - Collect on Delivery',
            '78' => 'Ground - Returns Plus - One Pickup Attempt',
            '90' => 'Ground - Returns - UPS Prints and Mails Label',
            'A0' => 'Next Day Air Early A.M. - Adult Signature Required',
            'A1' => 'Next Day Air Early A.M. - Saturday Delivery, Adult Signature Required',
            'A2' => 'Next Day Air - Adult Signature Required',
            'A8' => 'Ground - Adult Signature Required',
            'A9' => 'Next Day Air Early A.M. - Adult Signature Required, COD',
            'AA' => 'Next Day Air Early A.M. - Saturday Delivery, Adult Signature Required, COD');
    }

}

?>