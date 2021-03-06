<?php

namespace indextank\dotenv;

use Yii;
use Dotenv\Dotenv;
use yii\base\Component;
use yii\web\NotFoundHttpException;

class Loader extends Component
{
    /**
     * Load .env file from Yii2 project root directory.
     *
     * @param string $file
     * @return bool
     */
    public static function load($file = '.env')
    {
        if (class_exists('Yii', false)) {
            /*
             * Usually, the env is used before defining these aliases:
             * @vendor and @app. So, if you vendor is symbolic link,
             * Please register @vendor alias in bootstrap file or before
             * call env function.
             */
            if (Yii::getAlias('@vendor', false)) {
                $vendorDir = Yii::getAlias('@vendor');
                $path = dirname($vendorDir);
            } elseif (Yii::getAlias('@root', false)) {
                $path = Yii::getAlias('@root');
            } elseif (Yii::getAlias('@app', false)) {
                $path = Yii::getAlias('@app');
            } else {
                $yiiDir = Yii::getAlias('@yii');
                $path = dirname(dirname(dirname($yiiDir)));
            }
        } else {
            if (defined('VENDOR_PATH')) {
                $vendorDir = VENDOR_PATH;
            } else {
                /*
                 * If not found Yii class, will use composer vendor directory
                 * structure finding.
                 *
                 * Notice: this method are not handled process symbolic link!
                 */
                $vendorDir = dirname(dirname(dirname(dirname(__FILE__))));
            }
            $path = dirname($vendorDir);
        }

        /*
         * Get env file name from environment variable,
         * if COMPOSER_DOTENV_FILE have been set.
         */
        if (empty($file)) {
            $file = '.env';
        }

        /*
         * This program will not force the file to be loaded,
         * if the file does not exist then return.
         */
        $fileDir = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!file_exists($fileDir . $file)) {
            throw new NotFoundHttpException("Params file {$fileDir} . {$file} not found");
        }

        try {
            if (preg_match('~(\.env(\.|$))~', $fileDir . $file)) {
                return self::loadDotEnvFile($fileDir);
            }

            if (preg_match('~\.ini$~', $fileDir . $file)) {
                return self::loadIniFile();
            }
        } catch (\Exception $e) {
            throw new NotFoundHttpException("Failed loading params from $fileDir . $file\n" . $e->getMessage());
        }
    }

    protected static function loadDotEnvFile($fileDir)
    {
        if (class_exists('Dotenv\Dotenv')) {
            //$dotenv = \Dotenv\Dotenv::createImmutable($fileDir);
            //return $dotenv->load();
            $repository = \Dotenv\Repository\RepositoryBuilder::create()
                ->withReaders([
                    new \Dotenv\Repository\Adapter\EnvConstAdapter(),
                ])
                ->withWriters([
                    new \Dotenv\Repository\Adapter\EnvConstAdapter(),
                    new \Dotenv\Repository\Adapter\PutenvAdapter(),
                ])
                ->immutable()
                ->make();

            $dotenv = \Dotenv\Dotenv::create($repository, $fileDir);
            return $dotenv->load();
        }

        throw new NotFoundHttpException(
            "`vlucas/phpdotenv` library is required to parse .env files.\n" .
            "Please install it via composer: composer require vlucas/phpdotenv"
        );
    }

    protected function loadIniFile($paramsFile)
    {
        return parse_ini_file($paramsFile);
    }
}