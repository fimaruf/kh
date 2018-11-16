<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Flight extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {
//        return parent::toArray($request);

        $start = collect($this['frames'])->min('timestamp');
        $end = collect($this['frames'])->max('timestamp');
        $start_point = collect($this['frames'])->firstWhere('timestamp', '==', $start);
        $end_point = collect($this['frames'])->firstWhere('timestamp', '==', $end);

        $battery_percent = collect($this['frames'])->where('type', 'battery')->map(function ($item) {
            return $item['battery_percent'];
        });

        $battery_temperature = collect($this['frames'])->where('type', 'battery')->map(function ($item) {
            return $item['battery_temperature'];
        });

        $gps = collect($this['frames'])->where('type', 'gps')->map(function ($item) {
            return $item;
        });

//        dd($gps);

        if ($start_point && $start_point['type'] == 'gps' && $end_point ) {
            $start_p_lt = $start_point['lat'];
            $start_p_ln = $start_point['long'];

            $end_p_lt = $end_point['lat'];
            $end_p_ln = $end_point['long'];

            $distance = number_format($this->findDistance($start_p_lt, $start_p_ln, $end_p_lt, $end_p_ln, 'M'), 2, '.', '');
        }


        if ($start_point && $start_point['type'] == 'gps') {
            $address = $this->getAddress($start_point['lat'], $start_point['long']);
        }

        // Flight path
        if ($gps->isNotEmpty()) {
            $flight_path = $this->geoJson($gps);
        }

        return [
            'uuid' => $this['uuid'],
            'type' => $start_point['type'],
            'home_point' => ($start_point['type'] == 'gps' ? $start_point['lat'] . ',' . $start_point['long'] : null),
            'duration' => (float)number_format(($end - $start)/60000, 2, '.', ''),
            'aircraft_name' => $this['aircraft_name'] ,
            'aircraft_sn' => $this['aircraft_sn'],
            'battery_name' => $this['batteries'][0]['battery_name'],
            'battery_sn' => $this['batteries'][0]['battery_sn'],
            'flight_details' => [
                'battery_temp' => ($battery_temperature->isNotEmpty() ? $battery_temperature : null),
                'battery_percent' => ($battery_percent->isNotEmpty() ? $battery_percent : null),
                'flight_path' => (isset($flight_path) ? $flight_path : null),
                'distance' => (isset($distance) ? (float)$distance : null),
                'max_speed' => null,
                'address' => (isset($address) ? $address : null),
            ]
        ];
    }

    /**
     * Calculate distance between two pointers using longitude and latitude in requested unit
     *
     * @param $lat1
     * @param $lon1
     * @param $lat2
     * @param $lon2
     * @param $unit
     * @return float|int
     */
    private function findDistance($lat1, $lon1, $lat2, $lon2, $unit) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") { // assuming K as Kilometer
            return ($miles * 1.609344);
        } else if ($unit == "M") { // assuming M as meter
            return ($miles * 0.8684 * 1000);
        } else if ($unit == "N") { // assuming N as Nautical miles
            return ($miles * 0.8684);
        } else {
            return $miles; // else Miles
        }
    }

    /**
     * return formatted address from latitude and longitude using GoogleMapsAPI
     *
     * @param $lat
     * @param $lng
     * @return bool
     */
    private function getAddress($lat,$lng) {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.trim($lat).','.trim($lng).'&sensor=false&&key=AIzaSyB9hggeAJWYRXv9FhmsedHot_AC8MiWbg8';
        $json = @file_get_contents($url);
        $data=json_decode($json);
        $status = $data->status;
        if($status=="OK")
        {
            return $data->results[0]->formatted_address;
        }
        else
        {
            return false;
        }
    }

    /**
     * Generate an array of geoJson format for flight path
     *
     * @param $locales
     * @return array
     */
    private function geoJson ($locales) {
        $original_data = json_decode($locales, true);

        $coordinates = array();
        foreach($original_data as $key => $value) {
            $coordinates[] = array($value['lat'], $value['long']);
        }

        $new_data = array(
            'type' => 'FeatureCollection',
            'features' => array(
                'type' => 'Feature',
                'geometry' => array('type' => 'Point', 'coordinates' => $coordinates),
                'properties' => array(),
            ),
        );

        return $new_data;
    }

    /**
     * Calculate distance using 3 axis latitude, longitude, and altitude
     *
     * @param $lat1
     * @param $lon1
     * @param $alt1
     * @param $lat2
     * @param $lon2
     * @param $alt2
     * @param $unit
     */
    private function getDistance3d($lat1, $lon1, $alt1, $lat2, $lon2, $alt2, $unit) {

    }

    /**
     * Validate Flight information
     *
     * @return bool
     */
    private function validate() {
        return true;
    }

}
