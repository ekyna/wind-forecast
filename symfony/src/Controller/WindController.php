<?php

namespace App\Controller;

use App\Service\Path;
use App\Util\DateUtil;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class WindController
 * @package App\Controller
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class WindController
{
    /**
     * @var Path
     */
    private $path;


    /**
     * Constructor.
     *
     * @param Path $path
     */
    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    /**
     * Wind file download.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        $date = $request->get('date');
        $hour = $request->get('hour');

        $year = substr($date, 0, 4);
        $month = substr($date, 4, 2);
        $day = substr($date, 6, 2);

        $date = DateUtil::createUTC();
        $date->setDate($year, $month, $day);
        $date->setTime($hour, 0);

        if (!DateUtil::checkStep($date, 3)) {
            throw new NotFoundHttpException("Unexpected date.");
        }

        $resolution = $request->get('resolution');
        $type = $request->get('type');

        $path = $this->path->build($date, $resolution, $type);

        $fs = new Filesystem();

        if (!$fs->exists($path)) {
            throw new NotFoundHttpException("File not found.");
        }

        $response = new BinaryFileResponse($path, Response::HTTP_OK, [
            'Content-Type' => 'application/octet-stream',
        ]);

        if ($date < (DateUtil::createUTC())->modify('-3 hours')) {
            $response->setExpires((DateUtil::createUTC())->modify('+1 month'));
        } else {
            $response->setExpires((DateUtil::createUTC())->modify('+1 hour'));
        }

        return $response;
    }
}
