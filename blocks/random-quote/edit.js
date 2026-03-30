import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    ToggleControl,
    Button,
    Spinner,
    Notice
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function Edit({ attributes, setAttributes }) {
    const { showAuthor } = attributes;
    const [quote, setQuote] = useState(rqbEditorData.initialQuote);
    const [loading, setLoading] = useState(!rqbEditorData.initialQuote);
    const [error, setError] = useState(null);
    const [isRefreshing, setIsRefreshing] = useState(false);

    const fetchRandomQuote = async () => {
        if (isRefreshing) return;
        
        setIsRefreshing(true);
        setLoading(true);
        setError(null);

        try {
            const formData = new FormData();
            formData.append('action', 'rqb_refresh_quote');
            formData.append('nonce', rqbEditorData.nonce);

            const response = await fetch(rqbEditorData.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                setQuote(data.data);
            } else {
                setError(data.data || __('Failed to load quote', 'random-quote-block'));
            }
        } catch (err) {
            console.error('Error fetching quote:', err);
            setError(__('Network error. Please try again.', 'random-quote-block'));
        } finally {
            setLoading(false);
            setIsRefreshing(false);
        }
    };

    useEffect(() => {
        // Only fetch if no initial quote
        if (!quote && !loading) {
            fetchRandomQuote();
        }
    }, []);

    const blockProps = useBlockProps({
        className: 'random-quote-block'
    });

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Quote Settings', 'random-quote-block')} initialOpen={true}>
                    <ToggleControl
                        label={__('Show Author', 'random-quote-block')}
                        help={showAuthor 
                            ? __('Author will be displayed', 'random-quote-block')
                            : __('Author will be hidden', 'random-quote-block')}
                        checked={showAuthor}
                        onChange={(value) => setAttributes({ showAuthor: value })}
                    />
                    
                    <Button
                        onClick={fetchRandomQuote}
                        variant="secondary"
                        disabled={loading || isRefreshing}
                        style={{ marginTop: '10px', width: '100%' }}
                    >
                        {loading ? __('Loading...', 'random-quote-block') : __('Refresh Quote', 'random-quote-block')}
                    </Button>
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {loading ? (
                    <div style={{ textAlign: 'center', padding: '20px' }}>
                        <Spinner />
                        <p>{__('Loading quote...', 'random-quote-block')}</p>
                    </div>
                ) : error ? (
                    <Notice status="error" isDismissible={false}>
                        {error}
                        <Button 
                            onClick={fetchRandomQuote} 
                            variant="link" 
                            style={{ marginTop: '10px', display: 'block' }}
                        >
                            {__('Try Again', 'random-quote-block')}
                        </Button>
                    </Notice>
                ) : quote ? (
                    <div>
                        <blockquote className="random-quote-block__text">
                            "{quote.quote}"
                        </blockquote>
                        {showAuthor && quote.author && (
                            <cite className="random-quote-block__author">
                                — {quote.author}
                            </cite>
                        )}
                    </div>
                ) : (
                    <Notice status="warning" isDismissible={false}>
                        {__('No quotes available. Please fetch quotes from settings.', 'random-quote-block')}
                    </Notice>
                )}
            </div>
        </>
    );
}