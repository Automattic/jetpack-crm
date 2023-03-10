! ( function ( e, o ) {
	'object' === typeof exports && 'object' === typeof module
		? ( module.exports = o( require( 'moment' ), require( 'fullcalendar' ) ) )
		: 'function' === typeof define && define.amd
		? define( [ 'moment', 'fullcalendar' ], o )
		: 'object' === typeof exports
		? o( require( 'moment' ), require( 'fullcalendar' ) )
		: o( e.moment, e.FullCalendar );
} )( 'undefined' !== typeof self ? self : this, function ( e, o ) {
	return ( function ( e ) {
		/**
		 * @param t
		 */
		function o( t ) {
			if ( r[ t ] ) {
				return r[ t ].exports;
			}
			const n = ( r[ t ] = { i: t, l: ! 1, exports: {} } );
			return e[ t ].call( n.exports, n, n.exports, o ), ( n.l = ! 0 ), n.exports;
		}
		var r = {};
		return (
			( o.m = e ),
			( o.c = r ),
			( o.d = function ( e, r, t ) {
				o.o( e, r ) ||
					Object.defineProperty( e, r, { configurable: ! 1, enumerable: ! 0, get: t } );
			} ),
			( o.n = function ( e ) {
				const r =
					e && e.__esModule
						? function () {
								return e.default;
						  }
						: function () {
								return e;
						  };
				return o.d( r, 'a', r ), r;
			} ),
			( o.o = function ( e, o ) {
				return Object.prototype.hasOwnProperty.call( e, o );
			} ),
			( o.p = '' ),
			o( ( o.s = 117 ) )
		);
	} )( {
		0: function ( o, r ) {
			o.exports = e;
		},
		1: function ( e, r ) {
			e.exports = o;
		},
		117: function ( e, o, r ) {
			Object.defineProperty( o, '__esModule', { value: ! 0 } ), r( 118 );
			const t = r( 1 );
			t.datepickerLocale( 'es-do', 'es', {
				closeText: 'Cerrar',
				prevText: '&#x3C;Ant',
				nextText: 'Sig&#x3E;',
				currentText: 'Hoy',
				monthNames: [
					'enero',
					'febrero',
					'marzo',
					'abril',
					'mayo',
					'junio',
					'julio',
					'agosto',
					'septiembre',
					'octubre',
					'noviembre',
					'diciembre',
				],
				monthNamesShort: [
					'ene',
					'feb',
					'mar',
					'abr',
					'may',
					'jun',
					'jul',
					'ago',
					'sep',
					'oct',
					'nov',
					'dic',
				],
				dayNames: [ 'domingo', 'lunes', 'martes', 'mi??rcoles', 'jueves', 'viernes', 's??bado' ],
				dayNamesShort: [ 'dom', 'lun', 'mar', 'mi??', 'jue', 'vie', 's??b' ],
				dayNamesMin: [ 'D', 'L', 'M', 'X', 'J', 'V', 'S' ],
				weekHeader: 'Sm',
				dateFormat: 'dd/mm/yy',
				firstDay: 1,
				isRTL: ! 1,
				showMonthAfterYear: ! 1,
				yearSuffix: '',
			} ),
				t.locale( 'es-do', {
					buttonText: { month: 'Mes', week: 'Semana', day: 'D??a', list: 'Agenda' },
					allDayHtml: 'Todo<br/>el d??a',
					eventLimitText: 'm??s',
					noEventsMessage: 'No hay eventos para mostrar',
				} );
		},
		118: function ( e, o, r ) {
			! ( function ( e, o ) {
				o( r( 0 ) );
			} )( 0, function ( e ) {
				const o = 'ene._feb._mar._abr._may._jun._jul._ago._sep._oct._nov._dic.'.split( '_' ),
					r = 'ene_feb_mar_abr_may_jun_jul_ago_sep_oct_nov_dic'.split( '_' ),
					t = [
						/^ene/i,
						/^feb/i,
						/^mar/i,
						/^abr/i,
						/^may/i,
						/^jun/i,
						/^jul/i,
						/^ago/i,
						/^sep/i,
						/^oct/i,
						/^nov/i,
						/^dic/i,
					],
					n = /^(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre|ene\.?|feb\.?|mar\.?|abr\.?|may\.?|jun\.?|jul\.?|ago\.?|sep\.?|oct\.?|nov\.?|dic\.?)/i;
				return e.defineLocale( 'es-do', {
					months: 'enero_febrero_marzo_abril_mayo_junio_julio_agosto_septiembre_octubre_noviembre_diciembre'.split(
						'_'
					),
					monthsShort: function ( e, t ) {
						return e ? ( /-MMM-/.test( t ) ? r[ e.month() ] : o[ e.month() ] ) : o;
					},
					monthsRegex: n,
					monthsShortRegex: n,
					monthsStrictRegex: /^(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)/i,
					monthsShortStrictRegex: /^(ene\.?|feb\.?|mar\.?|abr\.?|may\.?|jun\.?|jul\.?|ago\.?|sep\.?|oct\.?|nov\.?|dic\.?)/i,
					monthsParse: t,
					longMonthsParse: t,
					shortMonthsParse: t,
					weekdays: 'domingo_lunes_martes_mi??rcoles_jueves_viernes_s??bado'.split( '_' ),
					weekdaysShort: 'dom._lun._mar._mi??._jue._vie._s??b.'.split( '_' ),
					weekdaysMin: 'do_lu_ma_mi_ju_vi_s??'.split( '_' ),
					weekdaysParseExact: ! 0,
					longDateFormat: {
						LT: 'h:mm A',
						LTS: 'h:mm:ss A',
						L: 'DD/MM/YYYY',
						LL: 'D [de] MMMM [de] YYYY',
						LLL: 'D [de] MMMM [de] YYYY h:mm A',
						LLLL: 'dddd, D [de] MMMM [de] YYYY h:mm A',
					},
					calendar: {
						sameDay: function () {
							return '[hoy a la' + ( 1 !== this.hours() ? 's' : '' ) + '] LT';
						},
						nextDay: function () {
							return '[ma??ana a la' + ( 1 !== this.hours() ? 's' : '' ) + '] LT';
						},
						nextWeek: function () {
							return 'dddd [a la' + ( 1 !== this.hours() ? 's' : '' ) + '] LT';
						},
						lastDay: function () {
							return '[ayer a la' + ( 1 !== this.hours() ? 's' : '' ) + '] LT';
						},
						lastWeek: function () {
							return '[el] dddd [pasado a la' + ( 1 !== this.hours() ? 's' : '' ) + '] LT';
						},
						sameElse: 'L',
					},
					relativeTime: {
						future: 'en %s',
						past: 'hace %s',
						s: 'unos segundos',
						ss: '%d segundos',
						m: 'un minuto',
						mm: '%d minutos',
						h: 'una hora',
						hh: '%d horas',
						d: 'un d??a',
						dd: '%d d??as',
						M: 'un mes',
						MM: '%d meses',
						y: 'un a??o',
						yy: '%d a??os',
					},
					dayOfMonthOrdinalParse: /\d{1,2}??/,
					ordinal: '%d??',
					week: { dow: 1, doy: 4 },
				} );
			} );
		},
	} );
} );
