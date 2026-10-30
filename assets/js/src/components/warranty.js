// Digital warranty registration & lookup.
import { rest } from '../lib/api';

export default () => ({
  tab: 'register',
  order: '',
  phone: '',
  loading: false,
  error: '',
  records: [],

  async submit() {
    this.loading = true;
    this.error = '';
    try {
      this.records = (await rest('warranty', { method: 'POST', body: { order: this.order, phone: this.phone } })).records;
    } catch (error) {
      this.records = [];
      this.error = error.message;
    } finally {
      this.loading = false;
    }
  },
});
