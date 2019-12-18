<?php
/**
 * Copyright (c) 2016-2019 Mastercard
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class Mastercard_Mpgs_Model_Feed extends Mage_Core_Model_Abstract
{
    const XML_FREQUENCY_PATH = 'mastercard/adminnotification/frequency';
    const XML_FEED_URL_PATH = 'mastercard/adminnotification/feed_url';

    /**
     * @return $this
     */
    public function checkUpdate()
    {
        if (($this->getFrequency() + $this->getLastUpdate()) > time()) {
            return $this;
        }

        $feedData = array();

        $feedXml = $this->getFeedData();

        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
                $feedData[] = array(
                    'severity'      => (int)$item->severity,
                    'date_added'    => $this->getDate((string)$item->pubDate),
                    'title'         => (string)$item->title,
                    'description'   => (string)$item->description,
                    'url'           => (string)$item->link,
                );
            }

            if ($feedData) {
                Mage::getModel('adminnotification/inbox')->parse(array_reverse($feedData));
            }

        }
        $this->setLastUpdate();

        return $this;
    }

    /**
     * Retrieve DB date from RSS date
     *
     * @param string $rssDate
     * @return string YYYY-MM-DD YY:HH:SS
     */
    public function getDate($rssDate)
    {
        return gmdate('Y-m-d H:i:s', strtotime($rssDate));
    }

    /**
     * Retrieve Update Frequency
     *
     * @return int
     */
    public function getFrequency()
    {
        return Mage::getStoreConfig(self::XML_FREQUENCY_PATH) * 3600;
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return Mage::app()->loadCache('mastercard_admin_notifications_lastcheck');
    }

    /**
     * Retrieve feed data as XML element
     *
     * @return SimpleXMLElement
     */
    public function getFeedData()
    {
        try {
            $curl = new Varien_Http_Adapter_Curl();
            $curl->setConfig(array(
                'timeout' => 2
            ));

            $curl->write(Zend_Http_Client::GET, Mage::getStoreConfig(self::XML_FEED_URL_PATH), '1.0');
            $data = $curl->read();

            if ($data === false) {
                return false;
            }

            $data = preg_split('/^\r?$/m', $data, 2);
            $data = trim($data[1]);
        }  catch (Exception $e) {
            return null;
        } finally {
            if (isset($curl)) {
                $curl->close();
            }
        }

        try {
            $xml  = new SimpleXMLElement($data);
        }  catch (Exception $e) {
            return null;
        }

        return $xml;
    }

    /**
     * Set last update time (now)
     *
     * @return $this
     */
    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), 'admin_notifications_lastcheck');
        return $this;
    }
}
