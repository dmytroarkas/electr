<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index(): Response
    {
        $dir = "data/";
        $files = [];
        if (is_dir($dir) && ($handle = opendir($dir))) {
            while (($file = readdir($handle)) !== false) {
                if (!in_array($file, [".", "..", ".gitignore"])) {
                    $files[] = $file;
                }
            }
        }

        return $this->render("index/index.html.twig", ["files" => $files]);
    }

    /**
     * @Route("/run")
     */
    public function run(KernelInterface $kernel): Response
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput(["command" => "app:autoria"]);
        $output = new NullOutput();
        $application->run($input, $output);

        return $this->redirectToRoute("app_index_index");
    }
}
