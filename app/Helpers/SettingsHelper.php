<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class SettingsHelper
{
    /**
     * Get contact information from settings table
     * 
     * @return array
     */
    public static function getContactInformation(): array
    {
        $defaultContact = [
            'email' => 'cs@ecopestcontrol.co.id',
            'website' => 'https://ecopestcontrol.co.id',
            'phone' => '0811-385-2772',
            'company_name' => 'PT. Ecodwi Jaya Abadi',
            'address' => 'Jl. Kebo Iwa Utara Gg.XV Blok C-4 Padangsambian Kaja, Denpasar Barat Denpasar, Bali 80117',
            'npwp' => '83.953.167.0-901.000'
        ];

        try {
            $setting = Setting::where('key', 'contact_information')->first();

            if (!$setting || !$setting->value) {
                return $defaultContact;
            }

            $contactInfo = is_array($setting->value) ? $setting->value : json_decode($setting->value, true);

            if (!is_array($contactInfo)) {
                return $defaultContact;
            }

            // Merge with defaults to ensure all needed fields are present
            return array_merge($defaultContact, $contactInfo);
        } catch (\Exception $e) {
            // Log error and return default contact
            Log::error('Failed to retrieve contact information from settings: ' . $e->getMessage());
            return $defaultContact;
        }
    }

    /**
     * Get contact information formatted for email templates
     * 
     * @return array
     */
    public static function getEmailContactInfo(): array
    {
        $contact = self::getContactInformation();

        return [
            'company_name' => $contact['company_name'],
            'address' => $contact['address'],
            'email' => $contact['email'],
            'phone' => $contact['phone'],
            'website' => $contact['website'],
            'npwp' => $contact['npwp'],
            'bank_accounts' => self::getBankAccounts()
        ];
    }

    /**
     * Get bank accounts from settings table
     * 
     * @return array
     */
    public static function getBankAccounts(): array
    {
        try {
            $setting = Setting::where('key', 'banks')->first();

            if (!$setting || !$setting->value) {
                return [];
            }

            $bankAccounts = is_array($setting->value) ? $setting->value : json_decode($setting->value, true);

            return is_array($bankAccounts) ? $bankAccounts : [];
        } catch (\Exception $e) {
            Log::error('Failed to retrieve bank accounts from settings: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a specific setting value by key
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getSetting(string $key, $default = null)
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return $setting->value ?? $default;
        } catch (\Exception $e) {
            Log::error("Failed to retrieve setting '{$key}': " . $e->getMessage());
            return $default;
        }
    }
}
