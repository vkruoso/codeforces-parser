<?php
namespace CodeforcesParser;

use Guzzle\Http\Client;

/**
 * Codeforces Parser
 *
 * @version 1.0
 */
class Parser
{
    /**
     * @var string The contest ID to be parsed
     */
    protected $contestId;

    /**
     * @var array The problems we should get input/output
     */
    protected $problems;

    /**
     * @var Guzzle\Http\Client Instance of the Guzzle Client
     */
    protected $guzzleClient;

    /**
     * @var string The path to write files
     */
    protected $basePath;

    /**
     * @var string The language in which the coder will write
     */
    protected $language;

    /**
     * @var array The languages extenstions accepted
     */
    protected $languages = array(
      'c', 'cpp', 'java', 'pas', 'php', 'js', 'py'
    );

    /**
     * Parser constructor
     *
     * If $problems is null it tries to auto discover the problems in the
     * contest.
     *
     * @param string $contestId The contest ID
     * @param array  $problems  Problems to be parsed
     * @param string $basePath  The path to write files
     */
    public function __construct($contestId,$language,$basePath=".",$problems=null)
    {
        $this->guzzleClient = new \Guzzle\Http\Client("http://www.codeforces.com");
        $this->DOMDocument = new \DOMDocument();
        $this->contestId = $contestId;
        $this->problems = $problems;
        $this->basePath = $basePath;
        $this->language = $language;
    }

    /**
     * Parse problems
     *
     * Requests and parses all problems in configured. Creates the
     * input/output files and copy the templates files and scripts to the
     * target directory.
     *
     * @return bool TRUE if success, FALSE otherwise
     */
    public function parse()
    {
        // check language
        if(!array_search($this->language,$this->languages)){
          print "Unsupported language: ".$this->language.".\n";
          return false;
        }

        // discover problems if necessary
        if($this->problems==null) $this->problems = $this->discoverProblems();

        print "Retrieving problems: ".implode($this->problems,', ').".\n";
        $problems = $this->getProblems();
        foreach($problems as $problem){
            $inout = $this->parseProblem($problem['problem'],$problem['html']);
            $this->createFiles($problem['problem'],$inout);
        }

        // copy template files
        $handle = opendir('template/');
        if(!$handle) return false;
        while(false!=($entry=readdir($handle))){
            if($entry=='.' or $entry=='..') continue;

            $ext = pathinfo($entry,PATHINFO_EXTENSION);
            if($ext!='') continue;

            copy('template/'.$entry,$this->basePath.'/'.$entry);
            if($entry=='test' or $entry=='clear')
                chmod($this->basePath.'/'.$entry,0744);
        }

        return true;
    }

    /**
     * Discover problems
     *
     * In the case the class is instantiated without the list of problems to
     * be retrieved, this method will request the contest page and create the
     * array with the problem list for that particular contest.
     *
     * @return array List of problems in the contest or FALSE if it fails
     */
    private function discoverProblems()
    {
        $response = $this->guzzleClient->get('contest/'.$this->contestId)->send();
        if($response->getStatusCode()!=200) return false;
        $html = $response->getBody()->__toString();
        @$this->DOMDocument->loadHTML($html);
        $xpath = new \DOMXPath($this->DOMDocument);
        $list = $xpath->query("//select[@name='submittedProblemIndex']/option/@value");
        foreach($list as $item)
            if($item->value and $item->value!='generalAnnouncement')
                $tmp[$item->value]=true;
        foreach($tmp as $key=>$value) $res[] = $key;

        return $res;
    }

    /**
     * Get problems from codeforces
     *
     * Will request all the problems pages at once using Guzzle.
     *
     * @return array Each element is an array with 'problem' and 'html' keys
     */
    private function getProblems()
    {
        foreach($this->problems as $problem){
            $gets[] = $this->guzzleClient->get(
                array('contest/{contest}/problem/{problem}',array(
                    'contest' => $this->contestId,
                    'problem' => $problem))
            );
        }
        $responses = $this->guzzleClient->send($gets);
        foreach($responses as $index => $response){
            if($response->getStatusCode()!=200) return false;
            $p[] = array('problem' => $this->problems[$index],
                'html' => $response->getBody()->__toString());
        }

        return $p;
    }

    /**
     * Parse a problem HTML
     *
     * Parses a problem HTML and discover all the inputs and outputs of the
     * problem.
     *
     * @param string $problem  The problem name
     * @param string $html     The HTML string from the problem's page
     *
     * @return array Array with 'in' and 'out' keys. Each key have an array
     *               with all inputs and outputs as a string.
     */
    private function parseProblem($problem,$html)
    {
        @$this->DOMDocument->loadHTML($html);
        $xpath = new \DOMXPath($this->DOMDocument);
        $inputs = $xpath->query("//pre/parent::div[@class='input']/pre");
        $outputs = $xpath->query("//pre/parent::div[@class='output']/pre");
        if($inputs->length==0 or $outputs->length==0) return false;
        if($inputs->length!=$outputs->length) return false;
        foreach($inputs as $input) $in[] = $this->parseInputOutput($input);
        foreach($outputs as $output) $out[] = $this->parseInputOutput($output);

        return array('in'=>$in,'out'=>$out);
    }

    /**
     * Transform a single input/output to a string.
     *
     * @param DOMNode $data The DOM element that contains the input or output
     *
     * @return string The input or output as a string.
     */
    private function parseInputOutput($data)
    {
        $raw = "";
        for($i=0;$i<$data->childNodes->length;$i++){
            $child = $data->childNodes->item($i);
            if($child instanceof \DOMText)
                $raw .= $child->nodeValue."\n";
        }

        return $raw;
    }

    /**
     * Create all files related to the problem
     *
     * Creates all input and output files, as well as the model program
     *
     * @param string $problem The problem name
     * @param array  $inout   Array with input and output as strings
     *
     * @return bool TRUE if success, FALSE otherwise
     */
    private function createFiles($problem,$inout)
    {
        $size = count($inout['in']);
        for($i=0;$i<$size;$i++){
            file_put_contents($this->basePath.'/'.$problem.'.in'.($i+1),$inout['in'][$i]);
            file_put_contents($this->basePath.'/'.$problem.'.out'.($i+1),$inout['out'][$i]);
        }

        // copy template file to a problem file
        $templateFile = './template/model.'.$this->language;
        $problemFile = $this->basePath.'/'.$problem.'.'.$this->language;

        if(!file_exists($templateFile)){
          print "Unable to find file: ".$templateFile."\n";
          return false;
        }

        if(!file_exists($problemFile))
            copy($templateFile,$this->basePath.'/'.$problem.'.'.$this->language);

        return true;
    }
}
