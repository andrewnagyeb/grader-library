<?php

namespace Yusufs;

use Symfony\Component\Filesystem\Filesystem;
use Yusufs\Diff;

/*
 |----------------------------------------------------------
 | Grader Class
 |----------------------------------------------------------
 |
 | an extension for make online judge system based on PHP
 | 
 | Author       : Yusuf Syaifudin
 | Author email : yusuf.syaifudin@gmail.com
 | Author url   : http://yusyaif.com/
 | Created at   : Tuesday, November 4, 2014
 |----------------------------------------------------------
 | Developer        : Andrew Nagyeb
 | Developer Email  : andrewnajeeb@fci.helwan.edu.eg
 | Developed at     : Tuesday, September 12, 2017
 |----------------------------------------------------------
*/

class Grader
{
    public $storage_path;

    /**
     * Grader constructor.
     * @param $storage_path
     */
    public function __construct($storage_path)
    {
        $this->storage_path = $storage_path;
    }

    /**
     * Comparing and judging an output between two program
     *
     * @param string $program1 => filename, usually an admin program (compiled code/script)
     * @param string $program2 => filename, usually an user program (compiled code/script)
     * @param string $input => filename
     * @param integer $timelimit in seconds
     * @param integer $memorylimit in kilobyte
     *
     * @return array
     */
    public function compareFiles($program1_path, $program2_path, $options)
    {
        try {
            $diff = new Diff;
            $diff->file1 = $program1_path;
            $diff->file2 = $program2_path;
            $isDifferent = $diff->isDifferent($options);
            $isSame = $diff->isSame($options);

            // return
            return [
                'judge' => [
                    'output_file_difference' => $isDifferent,
                    'output_file_similarity' => $isSame
                ]
            ];

        } catch (\Exception $e) {
            return [
                'judge' => false
            ];
        }
    }

    public function compareProgram($program1, $program2, $input, $timelimit, $memorylimit, $maxmemlimit, $maxtimelimit)
    {

        // check file existence
        $filesystem = new Filesystem();

        $program_path = $this->storage_path . 'compiled/';

        if (!$filesystem->exists($program_path . $program1)) {
            return [
                'success' => false,
                'message' => "program1 does not exist"
            ];
        }

        if (!$filesystem->exists($program_path . $program2)) {
            return [
                'success' => false,
                'message' => "program2 does not exist"
            ];
        }

        $input_path = $this->storage_path . 'input/';
        if (!$filesystem->exists($input_path . $input)) {
            return [
                'success' => false,
                'message' => "input file does not exist"
            ];
        }

        // now process the program
        try {
            // first run the first program
            $program1_response = self::run($program1, $input, $timelimit, $memorylimit, $maxmemlimit, $maxtimelimit);

            // run the second program with the same input
            $program2_response = self::run($program2, $input, $timelimit, $memorylimit, $maxmemlimit, $maxtimelimit);

            // then check the output difference
            $output_progam1 = $program1_response['detail']['path'] . $program1_response['detail']['filename'];
            $output_progam2 = $program2_response['detail']['path'] . $program2_response['detail']['filename'];

            $diff = new Diff;
            $diff->file1 = $output_progam1;
            $diff->file2 = $output_progam2;
            $isDifferent = $diff->isDifferent();
            $isSame = $diff->isSame();

            // return
            return [
                'judge' => [
                    'output_file_difference' => $isDifferent,
                    'output_file_similarity' => $isSame
                ],
                'program1' => $program1_response,
                'program2' => $program2_response
            ];

        } catch (\Exception $e) {

        }

    }

    /**
     * Save input file for testing when running program
     *
     * @param text $content
     * @param string $filename
     *
     * @return array
     */
    public function saveInput($content, $filename = null)
    {
        // check if file is not empty
        if (empty($content)) {
            return [
                'success' => false,
                'message' => "Content can't be empty."
            ];
        }

        $filesystem = new Filesystem();
        $path = $this->storage_path . 'input/';

        // check file existence
        if (!$filesystem->exists($path)) {

            try {
                $filesystem->mkdir($path);
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => "Can't create path to save file",
                    'detail' => [
                        'reason' => $e->getMessage()
                    ]
                ];
            }

        }


        try {

            $filename = ($filename === null) ? 'input_' . rand() . time() : $filename;
            $script = $path . $filename . '.txt';

            // save to files
            $filesystem->dumpFile($script, $content);

            $response = [
                'success' => true,
                'message' => 'File saved!',
                'detail' => [
                    'filename' => $filename . '.txt',
                    'path' => $path,
                    'extension' => 'txt'
                ]
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => '\Exception Error',
                'detail' => [
                    'reason' => $e->getMessage()
                ]
            ];
        }

        return $response;
    }


    /**
     * Save a script file from user or admin
     *
     * @param string $ext file extension
     * @param text $content
     * @param string $filename
     *
     * @return array
     */
    public function saveScript($ext, $content, $filename = null, $quiz_id, $problem_id)
    {
        // check permitted extension
        if ($ext !== 'c' && $ext !== 'cpp') {
            return [
                'success' => false,
                'message' => "Permitted extension are c or cpp"
            ];
        }

        // check if file is not empty
        if (empty($content)) {
            return [
                'success' => false,
                'message' => "Content can't be empty."
            ];
        }

        $filesystem = new Filesystem();
        $path = $this->storage_path . 'scripts/' . $quiz_id . DIRECTORY_SEPARATOR . $problem_id . DIRECTORY_SEPARATOR;


        if (!$filesystem->exists($path)) {

            try {
                $filesystem->mkdir($path);
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => "Can't create path to save file",
                    'detail' => [
                        'reason' => $e->getMessage()
                    ]
                ];
            }

        }


        try {

            $filename = ($filename === null) ? 'script_' . rand() . time() : $filename;
            $script = $path . $filename . '.' . $ext;

            // save to files
            $filesystem->dumpFile($script, $content);

            // the file
            $path_parts = pathinfo($script);
            $ext = $path_parts['extension'];


            $response = [
                'success' => true,
                'message' => 'File saved!',
                'detail' => [
                    'filename' => $filename . '.' . $ext,
                    'path' => $path,
                    'extension' => $ext
                ]
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => '\Exception Error',
                'detail' => [
                    'reason' => $e->getMessage()
                ]
            ];
        }

        return $response;
    }

    /**
     * Compile the script to executable program
     *
     * @param string $filename
     *
     * @return boolean
     */
    public function compile($filename, $quiz_id, $problem_id)
    {
        try {

            $filesystem = new Filesystem();

            // the code
            $code = $this->storage_path . 'scripts/' . $quiz_id . DIRECTORY_SEPARATOR . $problem_id . DIRECTORY_SEPARATOR . $filename;

            // the file
            $path_parts = pathinfo($code);

            // file extension
            $ext = $path_parts['extension'];

            // output
            $output_file = $this->storage_path . 'compiled/' . $filename;

            if ($filesystem->exists($code)) {

                return self::runCompiler($code, $ext, $output_file);
            } else {
                return [
                    'success' => false,
                    'message' => "File script is not exists."
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '\Exception Error',
                'detail' => [
                    'reason' => $e->getMessage()
                ]
            ];

        }
    }

    /**
     * Private method to call the compiler using exec function
     *
     * @param file $code
     * @param string $language
     * @param string path to output file $output_file
     *
     * @return array
     */
    private function runCompiler($code, $language, $output_file)
    {
        $filesystem = new Filesystem();

        // check if folder is exist, if not try to create it
        if (!$filesystem->exists($this->storage_path . 'compiled/')) {

            try {
                $filesystem->mkdir($this->storage_path . 'compiled/');
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => "Can't create path to save file",
                    'detail' => [
                        'reason' => $e->getMessage()
                    ]
                ];
            }
        }


        try {
            // compiling the code
            $compiler = dirname(__FILE__) . '/../bashcode/compile.sh';
            // dd($compiler);
            // prepare query
            $compile_query = $compiler . ' ' . $code . ' ' . $language . ' ' . $output_file;

            // exec compiler via bash
            exec($compile_query, $response, $status);

            // dd($response);
            $arr = array();
            foreach ($response as $value) {
                $split = preg_split("/[\:]+[\s,]+/", $value);

                $arr1 = array($split[0] => $split[1]);
                $arr = array_merge($arr, $arr1);
            }

            // return $arr; die();
            // dd($arr);
            // var_dump($arr); die();
            // var_dump($arr["compile_time"]); die();

            if ($status != 0) {
                return [
                    'status' => false,
                    'message' => 'Check permission.',
                    'detail' => [
                        'reason' => 'compile_error',
                        'time' => 0,
                        'time_unit' => 'ms',
                        'exit_code' => $status,
                        'program_path' => $code
                    ]
                ];
            }

            $exit_code = $arr["exit_code"];
            $compile_time = $arr["compile_time"];

            if ($exit_code != 0) {

                // get error message from compiled path
                // dd(getcwd());
                $compiled_file = $output_file;
                $message = file_get_contents($compiled_file);
                return [
                    'status' => false,
                    'message' => $message,
                    'detail' => [
                        'reason' => 'compile_error',
                        'time' => $compile_time,
                        'time_unit' => 'ms',
                        'exit_code' => $exit_code,
                        'program_path' => $code
                    ]
                ];

            } else {

                $message = "Now you can run this program.";

                return [
                    'status' => true,
                    'message' => $message,
                    'detail' => [
                        'reason' => 'compiled',
                        'time' => $compile_time,
                        'time_unit' => 'ms',
                        'exit_code' => $exit_code,
                        'program_path' => $code
                    ]
                ];
            }

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => '\Exception Error',
                'detail' => [
                    'reason' => $e->getMessage(),
                    'program_path' => $code
                ]
            ];
        }
    }

    /**
     * Run program with the input file
     *
     * @param string $program --> code filename
     * @param string $input_filename --> input filename
     * @param integer $timelimit in seconds
     * @param integer $memorylimit in kilobyte
     * @param integer $maxmemlimit in kilobyte
     * @param integer $maxtimelimit in seconds
     *
     * @return array
     */
    public function run($program, $input_filename, $timelimit, $memorylimit, $maxmemlimit, $maxtimelimit)
    {
        $filesystem = new Filesystem();

        // check folder to output file
        if (!$filesystem->exists($this->storage_path . 'output/')) {
            try {
                $filesystem->mkdir($this->storage_path . 'output/');
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => "Can't create path to save file",
                    'detail' => [
                        'reason' => $e->getMessage()
                    ]
                ];
            }
        }

        try {

            // check program file existence
            $program = $this->storage_path . 'compiled/' . $program;
            if (!$filesystem->exists($program)) {
                return [
                    'status' => false,
                    'message' => 'Program file not found'
                ];
            }

            // check input file existence
            $input_file = $this->storage_path . 'input/' . $input_filename;
            if (!$filesystem->exists($input_file)) {
                return [
                    'status' => false,
                    'message' => 'Input file not found'
                ];
            }

            // runner program
            $runner = dirname(__FILE__) . '/../bashcode/runner.sh';

            // output file
            $output_path = $this->storage_path . 'output/';
            $output_filename = time() . '_output_of_' . $input_filename;
            $output_file = $output_path . $output_filename;

            // prepare query
            $query = $runner . ' ' .
                $timelimit * 1000 . ' ' .
                $memorylimit * 1024 . ' ' .
                $program . ' ' .
                $input_file . ' ' .
                $output_file . ' ' .
                $maxtimelimit . ' ' .
                $maxmemlimit . ' ' .
                ' 2>&1';

            // exec runner via bash
            exec($query, $response, $status);

            // parsing the output
            $return = array();
            foreach ($response as $r) {
                $responseString = preg_split("/[\:][\s]+/", $r);
                $retVal = array(
                    $responseString[0] => $responseString[1]
                );
                $return = array_merge($return, $retVal);
            }

            if ($status != 0) {
                $return = [];
            }

            return [
                'status' => true,
                'message' => 'You can now evaluate the result.',
                'detail' => array_merge(
                    $return,
                    [
                        'cpu_unit' => 'ms',
                        'vsize_unit' => 'kB',
                        'rss_unit' => 'kB',
                        'filename' => $output_filename,
                        'path' => $output_path
                    ]
                )
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => '\Exception Error',
                'detail' => [
                    'reason' => $e->getMessage()
                ]
            ];
        }
    }

    public static function saveOutput($folder_path, $content, $filename = null)
    {
        // check if file is not empty
        if (!isset($content)) {
            return [
                'success' => false,
                'message' => "Content can't be empty."
            ];
        }

        $filesystem = new Filesystem();
        $path = $folder_path . 'output/';

        // check file existence
        if (!$filesystem->exists($path)) {

            try {
                $filesystem->mkdir($path);
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => "Can't create path to save file",
                    'detail' => [
                        'reason' => $e->getMessage()
                    ]
                ];
            }

        }
        try {

            $filename = ($filename === null) ? 'output_' . rand() . time() : $filename;
            $script = $path . $filename . '.txt';

            // save to files
            $filesystem->dumpFile($script, $content);

            $response = [
                'success' => true,
                'message' => 'File saved!',
                'detail' => [
                    'filename' => $filename . '.txt',
                    'path' => $path,
                    'extension' => 'txt'
                ]
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => '\Exception Error',
                'detail' => [
                    'reason' => $e->getMessage()
                ]
            ];
        }

        return $response;
    }
}	
