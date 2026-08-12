<?php

namespace App\Traits;

trait GridBuilderConverter
{
    /**
     * Convert Filament TipTap grid builder to PDF-compatible table layout
     */
    private function convertGridBuilderToTable($content)
    {
        if (empty($content)) {
            return $content;
        }

        // Use DOMDocument to parse and modify HTML
        $dom = new \DOMDocument();
        
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Clear any libxml errors
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $grids = $xpath->query('//div[contains(@class, "filament-tiptap-grid-builder")]');

        foreach ($grids as $grid) {
            $cols = $grid->getAttribute('data-cols') ?: '2';
            $columns = $xpath->query('.//div[contains(@class, "filament-tiptap-grid-builder__column")]', $grid);

            // Create table element
            $table = $dom->createElement('table');
            $table->setAttribute('class', 'pdf-grid pdf-grid-' . $cols . 'col');

            // Create table row
            $row = $dom->createElement('tr');

            // Convert each column to table cell
            foreach ($columns as $column) {
                $cell = $dom->createElement('td');
                
                // Copy all child nodes from column to cell
                while ($column->firstChild) {
                    $cell->appendChild($column->firstChild);
                }
                
                $row->appendChild($cell);
            }

            $table->appendChild($row);

            // Replace grid with table
            $grid->parentNode->replaceChild($table, $grid);
        }

        // Return the modified HTML
        return $dom->saveHTML();
    }
}
