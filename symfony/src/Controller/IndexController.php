<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class IndexController
 * @package App\Controller
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class IndexController
{
    public function __invoke(): Response
    {
        return new Response("ekyna/wind-forecast");
    }
}
