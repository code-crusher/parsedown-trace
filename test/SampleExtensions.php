<?php

class UnsafeExtension extends Parsedown
{
    protected function blockFencedCodeComplete($Block)
    {
        $text = $Block['element']['element']['text'];
        unset($Block['element']['element']['text']);

        // WARNING: There is almost always a better way of doing things!
        //
        // This example is one of them, unsafe behaviour is NOT needed here.
        // Only use this if you trust the input and have no idea what
        // the output HTML will look like (e.g. using an external parser).
        $Block['element']['element']['rawHtml'] = "<p>$text</p>";

        return $Block;
    }
}


class FigureExtension extends Parsedown
{
    protected function inlineImage($Excerpt)
    {
        if ( ! isset($Excerpt['text'][1]) or $Excerpt['text'][1] !== '[')
        {
            return;
        }

        $Excerpt['text'] = substr($Excerpt['text'], 1);

        $Link = $this->inlineLink($Excerpt);

        if ($Link === null)
        {
            return;
        }

        return array(
            'extent' => $Link['extent'] + 2,
            'element' => array(
                'name' => 'figure',
                'elements' => array(
                    array(
                        'name' => 'img',
                        'attributes' => array(
                            'src' => $Link['element']['attributes']['href'],
                            'alt' => $Link['element']['handler']['argument'],
                        ),
                        'autobreak' => true,
                    ),
                ),
            ),
        );
    }
}

class TrustDelegatedExtension extends Parsedown
{
    protected function blockFencedCodeComplete($Block)
    {
        $text = $Block['element']['element']['text'];
        unset($Block['element']['element']['text']);

        // WARNING: There is almost always a better way of doing things!
        //
        // This behaviour is NOT needed in the demonstrated case.
        // Only use this if you are sure that the result being added into
        // rawHtml is safe.
        // (e.g. using an external parser with escaping capabilities).
        $Block['element']['element']['rawHtml'] = "<p>$text</p>";
        $Block['element']['element']['allowRawHtmlInSafeMode'] = true;

        return $Block;
    }
}
