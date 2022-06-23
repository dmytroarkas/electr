<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    private KernelInterface $kernel;
    private int $page = 0;
    private array $result = [];

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @Route("/")
     */
    public function index(): Response
    {
        $dir = "data/";
        $files = [];
        if (is_dir($dir) && ($handle = opendir($dir))) {
            while (($file = readdir($handle)) !== false) {
                if (!in_array($file, [".", ".."])) {
                    array_push($files, $file);
                }
            }
        }
        return $this->render("index/index.html.twig", ["files" => $files]);
    }

    /**
     * @Route("/run")
     */
    public function run(): Response
    {
        $matches = $this->parseCarsList();
        if (count($matches) === 0) {
            file_put_contents(
                $this->kernel->getProjectDir() .
                    "/public/data/" .
                    date("Y-m-d_H-i-s") .
                    ".json",
                json_encode($this->result, JSON_UNESCAPED_UNICODE)
            );

            return $this->redirectToRoute("app_index_index");
        }
        foreach ($matches as $url) {
            $car = $this->parse($url);
            $this->result[] = $car;
        }
        $this->page++;

        return $this->run();
    }

    private function parseCarsList()
    {
        $pageList = file_get_contents(
            "https://auto.ria.com/uk/search/?indexName=auto&year[0].gte=2011&year[0].lte=2022&brand.id[0]=55&model.id[0]=36565&country.import.usa.not=-1&region.id[0]=10&city.id[0]=10&price.currency=1&sort[0].order=dates.created.desc&abroad.not=0&custom.not=1&size=10&scrollToAuto=32774984&page=" .
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
        //Номер
        $number = "";
        preg_match(
            '/<span class="state-num ua">(.*?)<span/m',
            $pageCar,
            $matches
        );
        if (isset($matches[1])) {
            $number = $matches[1];
        }
        //Детали
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
                    '/(?:<span class="car-color".*?><\/span>)|(?:<span class="point">•<\/span>)/m',
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
        //Кузов
        $body = "";
        preg_match("/<dd> Хетчбек (.*?)<\/dd>/m", $pageCar, $matches);
        if (isset($matches[1])) {
            $body = preg_replace(
                '/<span class="point">•<\/span>/m',
                "",
                $matches[1]
            );
        }
        //Описание
        $description = "";
        preg_match(
            '/<div class="full-description">(.*?)<\/div>/m',
            $pageCar,
            $matches
        );
        if (isset($matches[1])) {
            $description = $matches[1];
        }
        //Описание дополнительное
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
        //Фотки
        $photos = [];
        preg_match_all(
            '/<img class="outline m-auto" src="(.*?)" .*?>/m',
            $pageCar,
            $matches
        );
        if (isset($matches[1])) {
            $photos = $matches[1];
        }
        //Название и год
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
        //Цена
        $price = "";
        preg_match(
            '/<div class="price_value"> <strong class="">(.*?)<\/strong>/m',
            $pageCar,
            $matches
        );
        if (isset($matches[1])) {
            $price = $matches[1];
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
        ];
    }
}
