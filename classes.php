<?php
class someTestStuff
{
    protected $rdsClient = null;
    protected $profiler = null;

    public function __construct()
    {
    }

    public function setRdsClient($rdsClient)
    {
        $this->rdsClient = $rdsClient;

        return $this;
    }

    public function setProfiler($profiler)
    {
        $this->profiler = $profiler;

        return $this;
    }
}

class dummyProfiler
{
    protected $elements = [];

    protected $currentOrder = 0;

    protected $dummyElement = [
        'order' => 0,
        'label' => '',
        'timer' => 0,
        'stages' => [
            'begin' => 0,
            'end' => 0
        ]
    ];

    public function __construct()
    {
    }

    public function addOrUpdate($label, $stage = 'begin')
    {
        $newElement = $this->dummyElement;

        foreach ($this->elements as $key => $element) {
            if ($element['label'] === $label) {
                $element['timer'] = $element['stages']['end'] = microtime(true);

                $newElement = $element;
                $this->elements[$key] = $element;

                return true;
            }
        }

        if ($newElement == $this->dummyElement) {
            $newElement['label'] = $label;
            $newElement['timer'] = microtime();
            $newElement['timer'] = $this->currentOrder++;
            $newElement['stages']['begin'] = microtime(true);

            $this->elements[] = $newElement;
        }

        return $this;
    }

    public function getElements()
    {
        $formattedElements = $this->elements;

        foreach ($formattedElements as $key => $element) {
            if (isset($element['stages']) && isset($element['stages']['begin']) && isset($element['stages']['end'])) {
                $element['total'] = $element['stages']['end'] - $element['stages']['begin'];

                $formattedElements[$key] = $element;
            }
        }

        return $formattedElements;
    }
}