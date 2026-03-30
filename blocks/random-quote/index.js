import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import './editor.scss';      // استایل‌های ویرایشگر → index.css
import './style.scss';       // استایل‌های فرانت‌اند → style-index.css

registerBlockType(metadata.name, {
    ...metadata,
    edit: Edit,
    save: () => null,
});