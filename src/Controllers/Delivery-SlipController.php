<?php

namespace Delivery-Slip\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;

class Delivery-SlipController extends Controller
{
    /**
     * @param Twig $twig
     * @return string
     */
    public function getHelloWorldPage(Twig $twig):string
    {
        return $twig->render('Delivery-Slip::Index');
    }
}