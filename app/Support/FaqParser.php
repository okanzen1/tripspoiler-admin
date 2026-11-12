<?php

namespace App\Support;

/**
 * Yapıştırılan metni soru-cevap çiftlerine ayırır (SSS "Toplu ekle"; aynısı tarayıcıda resources/js/faq-bulk.js).
 *  1) "Q: …" / "A: …" (önünde numara olabilir: "1. Q: …"; "Soru:" / "Cevap:" da olur)
 *  2) numaralı: "1. Soru?" satırı, altındaki satırlar cevap
 *  3) numarasız: "?" ile biten satır soru, altındaki satırlar cevap
 */
class FaqParser
{
    private const Q = '/^(?:\d+\s*[.)-]\s*)?(?:Q|S|Soru|Question)\s*[:：]\s*(.*)$/iu';

    private const A = '/^(?:A|C|Cevap|Answer)\s*[:：]\s*(.*)$/iu';

    private const NUM = '/^\d+\s*[.)-]\s+(.*)$/u';

    /**
     * @return list<array{question: string, answer: string}>
     */
    public static function parse(string $text): array
    {
        $lines = array_map(fn ($line) => trim(preg_replace('/\s+/u', ' ', str_replace('**', '', $line))), preg_split('/\R/u', $text));
        $mode = collect($lines)->contains(fn ($l) => preg_match(self::Q, $l)) ? 'qa'
            : (collect($lines)->filter(fn ($l) => preg_match(self::NUM, $l))->count() >= 2 ? 'num' : 'mark');

        $pairs = [];
        $cur = null;
        $inAnswer = false;
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            if ($mode === 'qa' && preg_match(self::Q, $line, $m)) {
                $pairs[] = ['question' => $m[1], 'answer' => []];
                $cur = array_key_last($pairs);
                $inAnswer = false;
            } elseif ($mode === 'qa' && $cur !== null && preg_match(self::A, $line, $m)) {
                $inAnswer = true;
                if ($m[1] !== '') {
                    $pairs[$cur]['answer'][] = $m[1];
                }
            } elseif ($mode === 'num' && preg_match(self::NUM, $line, $m)) {
                $pairs[] = ['question' => $m[1], 'answer' => []];
                $cur = array_key_last($pairs);
            } elseif ($mode === 'mark' && str_ends_with($line, '?') && mb_strlen($line) < 300 && ($cur === null || $pairs[$cur]['answer'])) {
                $pairs[] = ['question' => $line, 'answer' => []];
                $cur = array_key_last($pairs);
            } elseif ($cur !== null) {
                if ($mode === 'qa' && !$inAnswer) {
                    $pairs[$cur]['question'] .= ' ' . $line;
                } else {
                    $pairs[$cur]['answer'][] = $line;
                }
            }
        }

        return array_values(array_filter(array_map(fn ($p) => [
            'question' => trim($p['question']),
            'answer' => trim(implode("\n", $p['answer'])),
        ], $pairs), fn ($p) => $p['question'] !== '' && $p['answer'] !== ''));
    }
}
