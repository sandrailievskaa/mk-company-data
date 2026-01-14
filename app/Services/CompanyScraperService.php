<?php

namespace App\Services;

use App\Models\Company;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class CompanyScraperService
{
    public function scrapeCompanies(string $sector): void
    {
        $browser = new HttpBrowser(HttpClient::create());

        $url = 'https://zk.mk/'.$sector;

        do {
            $crawler = $browser->request('GET', $url);
            $results = $crawler->filter('.result');

            foreach ($results as $result) {
                $company = $this->parseResult($result);
                Company::query()->updateOrCreate([
                    'name' => $company['name'],
                ], [
                    'city' => $company['city'],
                    'address' => $company['address'],
                    'phone' => $company['phone'],
                    'sector' => $sector,
                ]);
            }

            $url = $crawler->filter('.pagination a[rel="next"]')->count()
                ? $crawler->filter('.pagination a[rel="next"]')->attr('href')
                : null;

        } while ($url);
    }

    private function getText(Crawler $crawler, string $selector): ?string
    {
        return $crawler->filter($selector)->count()
            ? trim($crawler->filter($selector)->text())
            : null;
    }

    private function getAttr(Crawler $crawler, string $selector, string $attr): ?string
    {
        return $crawler->filter($selector)->count()
            ? $crawler->filter($selector)->attr($attr)
            : null;
    }

    private function parseResult($result): array
    {
        $result_crawler = new Crawler($result);

        $name = $this->getText(
            $result_crawler,
            '.companyname span[itemprop="name"]'
        );

        $city = $this->getText(
            $result_crawler,
            'ul.details li[origcaption="Место"]'
        );
        $city = $city ? str_replace('Место:', '', $city) : null;

        $address = $this->getText(
            $result_crawler,
            'ul.details li[origcaption="Адреса"]'
        );
        $address = $address ? str_replace('Адреса:', '', $address) : null;

        $phone = $this->getAttr(
            $result_crawler,
            '.tcall',
            'href'
        );
        $phone = $phone ? str_replace('tel:', '', $phone) : null;

        return [
            'name' => $name,
            'city' => $city,
            'address' => $address,
            'phone' => $phone,
        ];
    }
}
