<?php

namespace App\Services;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class SectorScraperService
{
    public function scrapeSectors(): array
    {
        $browser = new HttpBrowser(HttpClient::create());

        $url = 'https://zk.mk/page/activities';

        $crawler = $browser->request('GET', $url);

        $results = $crawler->filter('.searchresults ul li a');

        $sectors = [];

        foreach ($results as $result) {
            $result_crawler = new Crawler($result);
            $val = $result_crawler->attr('href');
            if ($val && $val !== '/' && $val !== 'javascript:z.resetOverrideLocation(') {
                $sectors[] = $val;
            }
        }

        return $sectors;
    }
}
