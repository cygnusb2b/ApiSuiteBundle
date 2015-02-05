<?php

namespace Cygnus\ApiSuiteBundle\ApiClient\Google;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;

class ApiClientGeoCode extends ApiClientAbstract
{
    /**
     * Gets the Google Maps Geocode data from an array of address parts.
     *
     * @param  array    $address
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getData(array $address)
    {
        $parts = [];
        foreach ($address as $part) {
            if (empty($part)) {
                continue;
            }
            $parts[] = urlencode($part);
        }
        $address = implode('+', $parts);

        $request = $this->httpKernel->createSimpleRequest(
            'http://maps.googleapis.com/maps/api/geocode/json',
            'GET',
            ['address' => $address, 'sensor' => true]
        );
        return $this->doRequest($request);
    }

    /**
     * Gets the latitude and longitude from an array of address parts.
     *
     * @param  array    $address
     * @return array|null
     */
    public function getLongAndLat(array $address)
    {
        $response = $this->getData($address);
        if (!$response->isSuccessful()) {
            return null;
        }

        $data = json_decode($response->getContent(), true);
        if (!isset($data['results'][0]['geometry']['location'])) {
            return null;
        }
        $location = $data['results'][0]['geometry']['location'];

        if (isset($location['lat']) && isset($location['lng'])) {
            return $location;
        }
        return null;
    }
}
