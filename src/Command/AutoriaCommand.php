<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AutoriaCommand extends Command
{
    protected static $defaultName = "app:autoria";

    private KernelInterface $kernel;

    private int $page = 0;

    private array $result = [];

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription("Parsing from auto.ria.com");
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $matches = $this->parseCarsList();
        if (count($matches) === 0) {
            file_put_contents(
                $this->kernel->getProjectDir() .
                    "/public/data/" .
                    "autoria_" .
                    date("Y-m-d_H-i-s") .
                    ".json",
                json_encode($this->result, JSON_UNESCAPED_UNICODE)
            );

            return Command::SUCCESS;
        }
        foreach ($matches as $url) {
            $car = $this->parse($url);
            $this->result[] = $car;
        }
        $this->page++;

        return $this->execute($input, $output);
    }

    private function parseCarsList()
    {
        $pageList = file_get_contents(
            "https://auto.ria.com/uk/search/?indexName=auto&year[0].gte=2013&year[0].lte=2022&brand.id[0]=55&model.id[0]=36565&country.import.usa.not=-1&price.currency=1&sort[0].order=dates.created.desc&abroad.not=0&custom.not=1&size=20&page=" .
                $this->page
        );
        preg_match_all(
            '/<a data-template-v="6" href="(.*?)" class="address" title=".*?" target="_self" >/m',
            $pageList,
            $matches
        );

        return $matches[1];
    }

    private function parse($url)
    {
        $pageCar = file_get_contents($url);
        //??????????
        $number = "";
        preg_match(
            '/<span class="state-num ua">(.*?)<span/m',
            $pageCar,
            $matches
        );
        if (isset($matches[1])) {
            $number = $matches[1];
        }
        //????????????
        $details = [];
        preg_match(
            '/<div class="box-panel description-car"><div class="technical-info" id="details"><dl class="unstyle">.*?<\/dl>/m',
            $pageCar,
            $matches
        );
        if (isset($matches[0])) {
            $detailsBlock = $matches[0];

            if (isset($detailsBlock[0]) && isset($detailsBlock[0][0])) {
                $detailsColorClean = preg_replace(
                    '/(?:<span class="car-color".*?><\/span>)|(?:<span class="point">???<\/span>)/m',
                    "",
                    $detailsBlock
                );
                preg_match_all(
                    '/<dd(?: class="mhide")?> *?<span class="label">(.*?)<\/span> *?<span class="argument">(.*?)<\/span><\/dd>/m',
                    $detailsColorClean,
                    $detailsItems
                );
            }
            if (isset($detailsItems[1]) && isset($detailsItems[2])) {
                $detailsItemsLabels = $detailsItems[1];
                $detailsItemsValues = $detailsItems[2];
            }
            foreach ($detailsItemsLabels as $key => $detailsItemsLabel) {
                if ($detailsItemsLabel && $detailsItemsValues[$key]) {
                    $details[$detailsItemsLabel] = $detailsItemsValues[$key];
                }
            }
        }
        //??????????
        $body = "";
        preg_match("/<dd> ?????????????? (.*?)<\/dd>/m", $pageCar, $matches);
        if (isset($matches[1])) {
            $body = preg_replace(
                '/<span class="point">???<\/span>/m',
                "",
                $matches[1]
            );
        }
        //????????????????
        $description = "";
        preg_match(
            '/<div class="full-description">(.*?)<\/div>/m',
            $pageCar,
            $matches
        );
        if (isset($matches[1])) {
            $description = $matches[1];
        }
        //???????????????? ????????????????????????????
        $showline = [];
        preg_match_all(
            '/<dd class="show-line"> *?<span class="label">(.*?)<\/span> <span class="argument">(.*?)(?:<span class="alert_state" data-tooltip=".*?">.*?<\/span>)?<\/span><\/dd>/m',
            $pageCar,
            $matches
        );
        if (count($matches[1]) > 0) {
            foreach ($matches[1] as $key => $label) {
                $showline[$label] = $matches[2][$key];
            }
        }
        //??????????
        $photos = [];
        preg_match_all(
            '/<img class="outline m-auto" src="(.*?)" .*?>/m',
            $pageCar,
            $matches
        );
        if (isset($matches[1])) {
            $photos = $matches[1];
        }
        //???????????????? ?? ??????
        $name = "";
        $year = "";
        preg_match(
            '/<h1 class="head" .*?>(.*?)([0-9]+)<\/h1>/m',
            $pageCar,
            $matches
        );
        if (isset($matches[1])) {
            $name = $matches[1];
        }
        if (isset($matches[2])) {
            $year = $matches[2];
        }
        //????????
        $price = "";
        preg_match(
            '/<div class="price_value"> <strong class="">(.*?)<\/strong>/m',
            $pageCar,
            $matches
        );
        if (isset($matches[1])) {
            $price = $matches[1];
        }
        //????????????
        $crumbs = "";
        preg_match_all(
            '/{"@type":"ListItem".*?"name":"(.*?)"}/m',
            $pageCar,
            $matches
        );
        if (isset($matches[1])) {
            $crumbs = implode(",", $matches[1]);
        }

        return [
            "name" => $name,
            "year" => $year,
            "price" => $price,
            "url" => $url,
            "number" => $number,
            "details" => $details,
            "body" => $body,
            "description" => $description,
            "showline" => $showline,
            "photos" => $photos,
            "crumbs" => $crumbs,
        ];
    }
}
