import React, { Component } from 'react';
import { getRateHistory, requestCancelTokenSource } from '../../utils/api';
import RateHistoryView from '../views/RateHistoryView';

class RateHistory extends Component {
  constructor(props) {
    super(props);
    this.state = {
      loading: true,
      items: [],
      code: this.props.match.params.code,
      date: this.defaultDate(),
      error: null,
    };
    this.cancelTokenSource = null;
  }

  hasBack() {
    return this.props.history && this.props.history.length > 1;
  }

  defaultDate() {
    const d = new Date();
    return d.toISOString().slice(0, 10);
  }

  componentDidMount() {
    this.load();
  }

  componentWillUnmount() {
    if (this.cancelTokenSource) {
      this.cancelTokenSource.cancel('Component unmounted');
    }
  }

  onDateChange = (e) => {
    const selectedDate = e.target.value;
    const today = new Date().toISOString().slice(0, 10);

    if (selectedDate > today) {
      this.setState({
        date: selectedDate,
        error:
          'Nie można wyświetlić danych historycznych dla dat z przyszłości. Proszę wybrać datę nie późniejszą niż dzisiaj.',
        loading: false,
        items: [],
      });
      return;
    }

    this.setState(
      {
        date: selectedDate,
        loading: true,
        error: null,
      },
      () => {
        this.load();
      }
    );
  };

  load() {
    if (this.cancelTokenSource) {
      this.cancelTokenSource.cancel('New request started');
    }

    this.cancelTokenSource = requestCancelTokenSource();

    const { code, date } = this.state;
    getRateHistory(code, { date, days: 14 }, this.cancelTokenSource.token)
      .then((response) => {
        const data = response.data || {};
        this.setState({ items: data.items || [], loading: false });
      })
      .catch((error) => {
        if (error && error.__CANCEL__) {
          return;
        }
        console.error(error);
        let errorMessage = 'Nie udało się załadować danych historycznych.';

        if (error.response && error.response.status === 404) {
          errorMessage =
            'Brak danych historycznych dla wybranej daty. Spróbuj wybrać inną datę.';
        } else if (error.response && error.response.status >= 500) {
          errorMessage =
            'Wystąpił problem z serwerem NBP. Spróbuj ponownie za chwilę.';
        }

        this.setState({ error: errorMessage, loading: false });
      });
  }

  render() {
    const { loading, items, code, date, error } = this.state;
    return (
      <RateHistoryView
        loading={loading}
        items={items}
        code={code}
        date={date}
        error={error}
        onDateChange={this.onDateChange}
        defaultDate={this.defaultDate()}
        hasBack={this.hasBack()}
      />
    );
  }
}

export default RateHistory;
