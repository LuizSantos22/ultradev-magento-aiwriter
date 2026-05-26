<?php
class UltraDev_AIWriter_Model_System_Config_Source_Api
{
    public function toOptionArray()
    {
        return [
            ['value' => 'gemini',   'label' => 'Gemini (Google)'],
            ['value' => 'deepseek', 'label' => 'DeepSeek'],
        ];
    }
}
