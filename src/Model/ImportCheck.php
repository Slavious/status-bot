<?php


namespace App\Model;


use Symfony\Component\VarDumper\VarDumper;

class ImportCheck
{
    private $domain;
    private $domainName;
    private $importType;

    const SCRIPT_URL = 'checkIt.php';
    const DOMAIN_KEY = 'domain';
    const IMPORT_KEY = 'import';

    const IMPORT_TYPE_PRODUCTS = 'products';
    const IMPORT_TYPE_COUPONS = 'coupons';
    const IMPORT_TYPE_CUSTOMERS = 'customers';
    const IMPORT_TYPE_INVOICES = 'invoices';

    public function __construct($domain, $domainName, $importType)
    {
        $this->domain     = $domain;
        $this->importType = $importType;
        $this->domainName = $domainName;
    }

    public function check()
    {
        $result = get_headers($this->createURL());
        foreach ($result as $item) {
            if (stripos($item, 'IMPORT_IS_ACTIVE') !== false) {
                return explode(':', $item)[1];
            }
        }
        return 0;
    }

    public function createURL()
    {
        return sprintf('%s%s?import=%s&domain=%s', $this->domain, self::SCRIPT_URL, $this->importType, $this->domainName);
    }
}