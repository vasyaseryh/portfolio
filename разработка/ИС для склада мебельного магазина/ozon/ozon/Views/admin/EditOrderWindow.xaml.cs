using ozon.Models;
using System;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using System.ComponentModel;
using System.Runtime.CompilerServices;
using System.Globalization;
using System.Windows.Media;

namespace ozon.Views
{
    public partial class EditOrderWindow : Window, INotifyPropertyChanged
    {
        private readonly OrderEditData _orderData;
        private decimal _unitPrice;
        private decimal _totalPrice;

        public event PropertyChangedEventHandler PropertyChanged;

        public decimal UnitPrice
        {
            get => _unitPrice;
            set { _unitPrice = value; OnPropertyChanged(); }
        }

        public decimal TotalPrice
        {
            get => _totalPrice;
            set { _totalPrice = value; OnPropertyChanged(); }
        }

        public int Quantity
        {
            get => _orderData.Quantity;
            set
            {
                _orderData.Quantity = value;
                OnPropertyChanged();
                // Пересчитываем сумму при изменении количества
                TotalPrice = UnitPrice * value;
            }
        }

        public EditOrderWindow(OrderEditData orderData)
        {
            _orderData = orderData;

            // Вычисляем цену за единицу
            _unitPrice = orderData.TotalPrice / (orderData.Quantity > 0 ? orderData.Quantity : 1);
            _totalPrice = orderData.TotalPrice;

            InitializeComponent();

            SetTextQualityOptions();
            InitializeDataDisplay();
            LoadPickupPoints();
            InitializeStatusComboBox();
            InitializeDateTimePickers();
        }

        private void SetTextQualityOptions()
        {
            TextOptions.SetTextFormattingMode(this, TextFormattingMode.Display);
            TextOptions.SetTextRenderingMode(this, TextRenderingMode.ClearType);
        }

        private void InitializeDataDisplay()
        {
            // Устанавливаем DataContext для привязок
            this.DataContext = this;

            // Отображаем наименование товара
            ProductNameText.Text = _orderData.Name;

            // Отображаем дату создания
            CreateDateText.Text = _orderData.CreateTime.ToString("dd.MM.yyyy HH:mm");

            // Устанавливаем начальное количество
            QuantityTextBox.Text = _orderData.Quantity.ToString();
        }

        private void LoadPickupPoints()
        {
            try
            {
                using (var context = new OzonContext())
                {
                    var pickupPoints = context.PickupPoints
                        .Select(pp => pp.Address)
                        .ToList();

                    AddressComboBox.ItemsSource = pickupPoints;

                    if (!string.IsNullOrEmpty(_orderData.Address))
                    {
                        AddressComboBox.SelectedItem = _orderData.Address;
                    }

                    if (AddressComboBox.SelectedItem == null && pickupPoints.Count > 0)
                    {
                        AddressComboBox.SelectedIndex = 0;
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Ошибка загрузки пунктов выдачи: {ex.Message}",
                    "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        private void InitializeStatusComboBox()
        {
            foreach (ComboBoxItem item in StatusComboBox.Items)
            {
                if (item.Content.ToString() == _orderData.Status)
                {
                    StatusComboBox.SelectedItem = item;
                    break;
                }
            }

            if (StatusComboBox.SelectedItem == null && StatusComboBox.Items.Count > 0)
            {
                StatusComboBox.SelectedIndex = 0;
            }
        }

        private void InitializeDateTimePickers()
        {
            // Инициализация даты и времени отгрузки
            if (_orderData.ShipmentTime.HasValue)
            {
                ShipmentDatePicker.SelectedDate = _orderData.ShipmentTime.Value.Date;
                ShipmentTimeTextBox.Text = _orderData.ShipmentTime.Value.ToString("HH:mm");
            }
            else
            {
                ShipmentDatePicker.SelectedDate = null;
                ShipmentTimeTextBox.Text = "12:00";
            }

            // Инициализация даты и времени поставки
            if (_orderData.DeliveryTime.HasValue)
            {
                DeliveryDatePicker.SelectedDate = _orderData.DeliveryTime.Value.Date;
                DeliveryTimeTextBox.Text = _orderData.DeliveryTime.Value.ToString("HH:mm");
            }
            else
            {
                DeliveryDatePicker.SelectedDate = null;
                DeliveryTimeTextBox.Text = "12:00";
            }
        }

        private DateTime? ParseDateTime(DatePicker datePicker, TextBox timeTextBox)
        {
            if (!datePicker.SelectedDate.HasValue)
                return null;

            var date = datePicker.SelectedDate.Value;
            var timeText = timeTextBox.Text;

            if (DateTime.TryParseExact(timeText, "HH:mm", CultureInfo.InvariantCulture,
                DateTimeStyles.None, out DateTime time))
            {
                return new DateTime(date.Year, date.Month, date.Day, time.Hour, time.Minute, 0);
            }
            else
            {
                return new DateTime(date.Year, date.Month, date.Day, 12, 0, 0);
            }
        }

        // Автоматический пересчет суммы при изменении количества
        private void QuantityTextBox_TextChanged(object sender, TextChangedEventArgs e)
        {
            if (int.TryParse(QuantityTextBox.Text, out int quantity) && quantity > 0)
            {
                Quantity = quantity;
            }
        }

        // Очистка даты отгрузки
        private void ClearShipmentButton_Click(object sender, RoutedEventArgs e)
        {
            ShipmentDatePicker.SelectedDate = null;
            ShipmentTimeTextBox.Text = "12:00";
        }

        // Очистка даты поставки
        private void ClearDeliveryButton_Click(object sender, RoutedEventArgs e)
        {
            DeliveryDatePicker.SelectedDate = null;
            DeliveryTimeTextBox.Text = "12:00";
        }

        private void SaveButton_Click(object sender, RoutedEventArgs e)
        {
            try
            {
                if (!ValidateInput())
                    return;

                SaveChanges();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Ошибка при сохранении: {ex.Message}",
                    "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        private bool ValidateInput()
        {
            if (!int.TryParse(QuantityTextBox.Text, out int quantity) || quantity <= 0)
            {
                MessageBox.Show("Введите корректное количество (больше 0)",
                    "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
                QuantityTextBox.Focus();
                return false;
            }

            if (AddressComboBox.SelectedItem == null)
            {
                MessageBox.Show("Выберите пункт выдачи",
                    "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
                AddressComboBox.Focus();
                return false;
            }

            if (StatusComboBox.SelectedItem == null)
            {
                MessageBox.Show("Выберите статус заказа",
                    "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
                StatusComboBox.Focus();
                return false;
            }

            return true;
        }

        private void SaveChanges()
        {
            using (var db = new OzonContext())
            {
                var order = db.Orders.FirstOrDefault(o => o.Id == _orderData.OrderId);
                if (order != null)
                {
                    var newQuantity = int.Parse(QuantityTextBox.Text);
                    var newAddress = AddressComboBox.SelectedItem.ToString();
                    var newStatus = ((ComboBoxItem)StatusComboBox.SelectedItem).Content.ToString();
                    var newShipmentTime = ParseDateTime(ShipmentDatePicker, ShipmentTimeTextBox);
                    var newDeliveryTime = ParseDateTime(DeliveryDatePicker, DeliveryTimeTextBox);

                    // Проверяем изменение количества товара
                    if (newQuantity != order.Quantity)
                    {
                        var product = db.Products.FirstOrDefault(p => p.Id == _orderData.ProductId);
                        if (product != null)
                        {
                            var quantityDifference = newQuantity - order.Quantity;

                            if (quantityDifference > 0 && product.Quantity < quantityDifference)
                            {
                                MessageBox.Show($"Недостаточно товара на складе. Доступно: {product.Quantity} шт.",
                                    "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
                                return;
                            }

                            product.Quantity -= quantityDifference;
                        }
                    }

                    // Обновляем данные заказа
                    order.Quantity = newQuantity;
                    order.Address = newAddress;
                    order.Status = newStatus;
                    order.ShipmentTime = newShipmentTime; // Может быть null
                    order.DeliveryTime = newDeliveryTime; // Может быть null
                    order.Price = (int)UnitPrice;

                    db.SaveChanges();

                    MessageBox.Show("Изменения успешно сохранены!",
                        "Успех", MessageBoxButton.OK, MessageBoxImage.Information);

                    DialogResult = true;
                    Close();
                }
                else
                {
                    MessageBox.Show("Заказ не найден в базе данных",
                        "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
                }
            }
        }

        private void CancelButton_Click(object sender, RoutedEventArgs e)
        {
            DialogResult = false;
            Close();
        }

        // Обработчики для сброса времени при сбросе даты
        private void ShipmentDatePicker_SelectedDateChanged(object sender, SelectionChangedEventArgs e)
        {
            if (!ShipmentDatePicker.SelectedDate.HasValue)
            {
                ShipmentTimeTextBox.Text = "12:00";
            }
        }

        private void DeliveryDatePicker_SelectedDateChanged(object sender, SelectionChangedEventArgs e)
        {
            if (!DeliveryDatePicker.SelectedDate.HasValue)
            {
                DeliveryTimeTextBox.Text = "12:00";
            }
        }

        protected virtual void OnPropertyChanged([CallerMemberName] string propertyName = null)
        {
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
        }
    }
}