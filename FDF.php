<?php
class FDF 
{
    // FDF File section elements and delimiters
    const FDF_HEADER          = '%FDF-1.2\n%\xe2\xe3\xcf\xd3\r\n1 0 obj\n<<\n/FDF\n';
    const FDF_FIELDS_OPEN     = '<<\n/Fields [\n';
    const FDF_FIELDS_CLOSE    = ']\n';
    const FDF_END_OBJ         = '>>\n>>\nendobj\n';
    const FDF_TRAILER         = 'trailer\n\n<<\n/Root 1 0 R\n>>\n';
    const FDF_EOF             = '%%EOF\n\x0a';

    // Templates to create entries withn the FDF file.
    const FDF_TEMPLATE_DATA   = 
        '<<\n/V{$VALUE}\n/T ({$NAME})\n{$IS_HIDDEN}\n{$IS_READONLY}\n>>\n';
    const FDF_TEMPLATE_STRING = 
        '<<\n/V /{$VALUE}\n/T ({$NAME})\n{$IS_HIDDEN}\n{$IS_READONLY}\n>>\n';
    const FDF_TEMPLATE_URL    = '/F ({$URL})\n';

    // FDF Specific field values
    const FDF_HIDDEN_SET      = '\SetF 2';
    const FDF_HIDDEN_CLEAR    = '\ClrF 2';
    const FDF_READONLY_SET    = '\SetFf 1';
    const FDF_READONLY_CLEAR  = '\ClrFf 1';
    const FDF_BOOL_TRUE       = 'Yes';
    const FDF_BOOL_FALSE      = 'No';

    // Constants to escape certain characters.
    const HASHMARK      = 0x23;
    const OPENPAREN     = 0x28;
    const CLOSEPAREN    = 0x29;
    const BACKSLASH     = 0x5c;

    private $data;
    private $readonlyFields;
    private $hiddenFields;

    /**
     * Create a FDF object from an array
     */
    function __construct(array $data, array $readonly=array(), $hidden=array(), 
                         $url=null) {
        $this->data= $data;
        $this->readonlyFields= $readonly;
        $this->hiddenFields= $hidden;
    }

    /**
     * Obtain the actual contents of the FDF file.
     */
    private function getContents() {
        $fdf= '';
        $fdf .= self::FDF_HEADER;
        $fdf .= self::FDF_FIELDS_OPEN;
        foreach($this->data as $name => $value)   
            if (gettype($value) == 'string')
                $fdf .= self::toFDFStringField($name, $value, 
                                               in_array($name, $this->hiddenFields),
                                               in_array($name, $this->readonlyFields));
            else 
                $fdf .= self::toFDFDataField($name, $value, 
                                               in_array($name, $this->hiddenFields),
                                               in_array($name, $this->readonlyFields));
        if (!empty($this->url))
            $fdf .= self::toFDFFormUrl($this->url);

        $fdf .= self::FDF_FIELDS_CLOSE;
        $fdf .= self::FDF_END_OBJ;
        $fdf .= self::FDF_TRAILER;
        $fdf .= self::FDF_EOF;
        return $fdf;
    }


    public function __toString() {
        return $this->getContents();
    }

    /**
     * Encode an FDF String field. 
     *
     * @param name       The name of the field.
     * @param value      The value of the field.
     * @param isHidden   If the field is hidden or not.
     * @param isReadonly If the field is in readonly mode or not.
     * 
     * @return The encoded FDF field as string.
     */
    private static function toFDFStringField($name, $value, 
                                             $isHidden=false, $isReadonly=false) {
        $val= $value;
        if (gettype($value) == 'boolean')
            $val = $value ? self::FDF_BOOL_TRUE : self::FDF_BOOL_FALSE;

        $VALUE= self::smartEncode($val);
        $NAME= self::smartEncode($name);
        $IS_HIDDEN= self::smartEncode($isHidden ? 
                                      self::FDF_HIDDEN_SET : self::FDF_HIDDEN_CLEAR);
        $IS_READONLY= self::smartEncode($isReadonly ? 
                                      self::FDF_READONLY_SET : self::FDF_READONLY_CLEAR);
        return self::FDF_TEMPLATE_STRING;
    }

    /**
     * 
     */
    private static function toFDFDataField($name, $value,
                                           $isHidden=false, $isReadonly=false) {

        $VALUE= self::smartEncode($value);
        $NAME= self::smartEncode($name);
        $IS_HIDDEN= self::smartEncode($isHidden ? 
                                      self::FDF_HIDDEN_SET : self::FDF_HIDDEN_CLEAR);
        $IS_READONLY= self::smartEncode($isReadonly ? 
                                      self::FDF_READONLY_SET : self::FDF_READONLY_CLEAR);
        return self::FDF_TEMPLATE_DATA;
    }


    /**
     * Escape strings that will go into the PDF file
     *
     * @param ss The string to be escaped.
     *
     * @return The escaped string.
     */
    private static function escapeString($ss) {
        $backslash= chr(self::BACKSLASH);
        $ss_esc= '';
        for( $i= 0; $i< strlen($ss); ++$i ) {
            $ordinal= ord($ss[$i]);
            if($ordinal == self::OPENPAREN  ||
               $ordinal == self::CLOSEPAREN || 
               $ordinal == self::BACKSLASH ) {
                $ss_esc.= $backslash . $ss[$i];
            } else if ( $ordinal < 32 || 126 < $ordinal) {
              $ss_esc.= sprintf("\\%03o", $ordinal); // use an octal code
            } else {
              $ss_esc.= $ss[$i];
            }
        }
        return $ss_esc;
    }

    /**
     * Escape a PDF name.
     *
     * @param ss The string to be escaped.
     *
     * @return The escaped name
     */
    private static function escapeName($ss) {
        $ss_esc= '';
        $ss_len= strlen( $ss );
        for( $ii= 0; $ii< $ss_len; ++$ii ) {
            if (ord($ss{$ii}) < 33 || 126 < ord($ss{$ii}) || 
                ord($ss{$ii})== self::HASHMARK ) {
            $ss_esc.= sprintf( "#%02x", ord($ss{$ii}) ); // use a hex code
              }
            else {
              $ss_esc.= $ss{$ii};
            }
        }
        return $ss_esc;
    }


    /**
     * Encode the string in UTF-16 BE, escaping the parens.
     * 
     * @param s The string to be encoded.
     * 
     * @return The encoded string.
     */
    private static function smartEncode($s) {
        $utf16= mb_convert_encoding($s, 'UTF-16BE');
        $safe= $utf16;
        $safe= mb_eregi_replace('\x00)', '\x00\\), '$utf16);
        $safe= mb_eregi_replace('\x00(', '\x00\\(, '$utf16);
        return $safe;
    }
}
