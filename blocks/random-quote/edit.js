import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

export default function Edit({ attributes, setAttributes }) {
    const [quote, setQuote] = useState(rqbEditorData.initialQuote);

    const refreshQuote = async () => {
        const formData = new FormData();
        formData.append('action', 'rqb_refresh_quote');
        formData.append('nonce', rqbEditorData.nonce);

        const response = await fetch(rqbEditorData.ajaxUrl, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            setQuote(data.data);
        }
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title="Settings">
                    <ToggleControl
                        label="Show Author"
                        checked={attributes.showAuthor}
                        onChange={(value) => setAttributes({ showAuthor: value })}
                    />

                    <Button onClick={refreshQuote}>
                        Refresh Quote
                    </Button>
                </PanelBody>
            </InspectorControls>

            <div {...useBlockProps()}>
                {quote && (
                    <>
                        <blockquote>{quote.quote}</blockquote>
                        {attributes.showAuthor && (
                            <cite>— {quote.author}</cite>
                        )}
                    </>
                )}
            </div>
        </>
    );
}