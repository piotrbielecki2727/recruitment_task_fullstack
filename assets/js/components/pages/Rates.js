import React, { Component } from 'react';
import { getRates, requestCancelTokenSource } from '../../utils/api';
import RatesView from '../views/RatesView';

class Rates extends Component {
  constructor() {
    super();
    this.state = { loading: true, items: [], date: null, error: null };
    this.cancelTokenSource = null;
  }

  componentDidMount() {
    this.load();
  }

  componentWillUnmount() {
    if (this.cancelTokenSource) {
      this.cancelTokenSource.cancel('Component unmounted');
    }
  }

  load() {
    if (this.cancelTokenSource) {
      this.cancelTokenSource.cancel('New request started');
    }

    this.cancelTokenSource = requestCancelTokenSource();

    getRates(this.cancelTokenSource.token)
      .then((response) => {
        const data = response.data || {};
        this.setState({
          items: data.items || [],
          date: data.effectiveDate || null,
          loading: false,
        });
      })
      .catch((error) => {
        if (error && error.__CANCEL__) {
          return;
        }
        console.error('API error:', error);
        this.setState({
          error: 'Nie udało się załadować kursów',
          loading: false,
        });
      });
  }

  render() {
    const { loading, items, date, error } = this.state;
    return (
      <RatesView loading={loading} items={items} date={date} error={error} />
    );
  }
}

export default Rates;
