<?php
namespace OfficeML;

use OfficeML\Processor\Tag;

class Processor
{
    const XSL_NS = 'http://www.w3.org/1999/XSL/Transform';
    const VALUES_PATH = 'values';

    /**
     * Wrap document content into xsl template.
     *
     * @param \DOMDocument $document
     * @return void
     */
    public static function wrapIntoTemplate(\DOMDocument $document)
    {
        $stylesheet = $document->createElementNS(self::XSL_NS, 'xsl:stylesheet');
        $stylesheet->setAttribute('version', '1.0');

        $output = $document->createElementNS(self::XSL_NS, 'xsl:output');
        $output->setAttribute('method', 'xml');
        $output->setAttribute('encoding', 'UTF-8'); // TODO variable encoding?
        $stylesheet->appendChild($output);

        $template = $document->createElementNS(self::XSL_NS, 'xsl:template');
        $template->setAttribute('match', '/' . self::VALUES_PATH);
        $template->appendChild($document->documentElement);
        $stylesheet->appendChild($template);

        $document->appendChild($stylesheet);
    }

    public static function insertTemplateLogic(Tag $tag, \DOMElement $node)
    {
        $template = $node->ownerDocument;

        $node->setAttribute('xml:space', 'preserve'); // TODO Fix whitespaces in mixed node

        /** @var $textNode \DOMText */
        foreach ($node->childNodes as $textNode) {
            $nodeValue = $textNode->nodeValue; // utf8_decode

            // before [[tag]] after
            $nodeValueParts = explode($tag->getTextContent(), $nodeValue, 2); // fix similar tags in one node

            if (count($nodeValueParts) === 2) {
                $textNode->nodeValue = ''; // reset

                // text before
                $before = $template->createTextNode($nodeValueParts[0]);
                $node->insertBefore($before, $textNode);

                // add xsl logic TODO Functions
                $placeholder = $template->createElementNS(self::XSL_NS, 'xsl:value-of');
                $placeholder->setAttribute('select', '/' . self::VALUES_PATH . '/' . $tag->getXmlPath());
                $node->insertBefore($placeholder, $textNode);

                // text after
                $after = $template->createTextNode($nodeValueParts[1]);
                $node->insertBefore($after, $textNode);

                $node->removeChild($textNode);

                return true;
            }
        }

        return false;
    }

    public static function escapeXsl(\DOMDocument $document)
    {
        $xpath = new \DOMXPath($document);
        // escape attr for xsl
        $nodeList = $xpath->query('//*[contains(@uri, "{") and contains(@uri ,"}")]');
        /** @var $node \DOMNode  */
        foreach ($nodeList as $node) {
            /** @var $attr \DOMAttr  */
            foreach ($node->attributes as $attr) {
                $attr->nodeValue = str_replace(array('{', '}'), array('{{', '}}'), $attr->nodeValue);
            }
        }
    }

    public static function undoEscapeXsl(\DOMDocument $document)
    {
        $xpath = new \DOMXPath($document);
        // escape attr for xsl
        $nodeList = $xpath->query('//*[contains(@uri, "{{") and contains(@uri ,"}}")]');
        /** @var $node \DOMNode  */
        foreach ($nodeList as $node) {
            /** @var $attr \DOMAttr  */
            foreach ($node->attributes as $attr) {
                $attr->nodeValue = str_replace(array('{{', '}}'), array('{', '}'), $attr->nodeValue);
            }
        }
    }
}