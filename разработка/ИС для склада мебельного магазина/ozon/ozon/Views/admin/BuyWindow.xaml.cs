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
    public partial class BuyWindow : Window, INotifyPropertyChanged
    {
        private readonly Product _product;
        private int _orderQuantity;
        private int _totalPrice = 0;
        private string _productName;
        private int _availableQuantity;
        private decimal _unitPrice;

        public event PropertyChangedEventHandler PropertyChanged;

        public string ProductName
        {
            get => _productName;
            set { _productName = value; OnPropertyChanged(); }
        }

        public int AvailableQuantity
        {
            get => _availableQuantity;
            set { _availableQuantity = value; OnPropertyChanged(); }
        }

        public decimal UnitPrice
        {
            get => _unitPrice;
            set { _unitPrice = value; OnPropertyChanged(); }
        }

        public int TotalPrice
        {
            get => _totalPrice;
            set { _totalPrice = value; OnPropertyChanged(); }
        }

        public BuyWindow(Product product)
        {
            _product = product;

            InitializeComponent();

            // Устанавливаем DataContext ДО инициализации данных
            this.DataContext = this;

            InitializeProductData();
            LoadPickupPoints();
            SetTextQualityOptions();
        }

        private void SetTextQualityOptions()
        {
            TextOptions.SetTextFormattingMode(this, TextFormattingMode.Display);
            TextOptions.SetTextRenderingMode(this, TextRenderingMode.ClearType);
        }

        private void InitializeProductData()
        {
            // Инициализируем свойства для привязки
            ProductName = _product.Name;
            AvailableQuantity = _product.Quantity;
            UnitPrice = _product.Price;
            TotalPrice = _product.Price;

            // Устанавливаем максимальное значение слайдера
            slider.Maximum = _product.Quantity;
        }

        private void LoadPickupPoints()
        {
            try
            {
                using (var context = new OzonContext())
                {
                    var pickups = context.PickupPoints.Select(pp => pp.Address).ToList();
                    pickupPointComboBox.ItemsSource = pickups;

                    if (pickups.Count > 0)
                    {
                        pickupPointComboBox.SelectedIndex = 0;
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Ошибка загрузки пунктов выдачи: {ex.Message}",
                    "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
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

        private void OrderButtonClick(object sender, RoutedEventArgs e)
        {
            try
            {
                if (!ValidateInput())
                    return;

                ProcessOrder();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Ошибка при оформлении заказа: {ex.Message}",
                    "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        private bool ValidateInput()
        {
            if (pickupPointComboBox.SelectedValue == null)
            {
                MessageBox.Show("Выберите пункт выдачи", "Внимание",
                    MessageBoxButton.OK, MessageBoxImage.Warning);
                return false;
            }

            _orderQuantity = (int)Math.Round(slider.Value);

            if (_orderQuantity > _product.Quantity)
            {
                MessageBox.Show($"Недостаточное количество товара! Доступно: {_product.Quantity} шт.",
                    "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
                return false;
            }

            if (_orderQuantity <= 0)
            {
                MessageBox.Show("Количество товара должно быть больше 0", "Ошибка",
                    MessageBoxButton.OK, MessageBoxImage.Error);
                return false;
            }

            return true;
        }

        private void ProcessOrder()
        {
            using (var db = new OzonContext())
            {
                var address = pickupPointComboBox.SelectedValue.ToString();
                var updatedProduct = db.Products.FirstOrDefault(p => p.Id == _product.Id);

                if (updatedProduct == null)
                {
                    MessageBox.Show("Товар не найден в базе данных", "Ошибка",
                        MessageBoxButton.OK, MessageBoxImage.Error);
                    return;
                }

                if (_orderQuantity > updatedProduct.Quantity)
                {
                    MessageBox.Show($"Количество товара изменилось. Доступно: {updatedProduct.Quantity} шт.",
                        "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
                    return;
                }

                updatedProduct.Quantity -= _orderQuantity;

                var order = new Order
                {
                    ProductId = _product.Id,
                    Quantity = _orderQuantity,
                    Address = address,
                    Status = "В пути",
                    Price = TotalPrice,
                    Create = DateTime.Now,
                    ShipmentTime = ParseDateTime(shipmentDatePicker, shipmentTimeTextBox),
                    DeliveryTime = ParseDateTime(deliveryDatePicker, deliveryTimeTextBox)
                };

                db.Orders.Add(order);
                db.SaveChanges();

                ShowSuccessMessage(address);
                Close();
            }
        }

        private void ShowSuccessMessage(string address)
        {
            string message = $"✅ Заказ успешно оформлен!\n\n" +
                           $"📦 Товар: {_product.Name}\n" +
                           $"🔢 Количество: {_orderQuantity} шт.\n" +
                           $"💰 Итоговая цена: {TotalPrice}₽\n" +
                           $"📍 Пункт выдачи: {address}\n" +
                           $"🕒 Дата создания: {DateTime.Now:dd.MM.yyyy HH:mm}";

            MessageBox.Show(message, "Успех", MessageBoxButton.OK, MessageBoxImage.Information);
        }

        private void MySlider_ValueChanged(object sender, RoutedPropertyChangedEventArgs<double> e)
        {
            var quantity = (int)Math.Round(slider.Value);
            TotalPrice = quantity * _product.Price;
        }

        private void ShipmentDatePicker_SelectedDateChanged(object sender, SelectionChangedEventArgs e)
        {
            if (!shipmentDatePicker.SelectedDate.HasValue)
            {
                shipmentTimeTextBox.Text = "12:00";
            }
        }

        private void DeliveryDatePicker_SelectedDateChanged(object sender, SelectionChangedEventArgs e)
        {
            if (!deliveryDatePicker.SelectedDate.HasValue)
            {
                deliveryTimeTextBox.Text = "12:00";
            }
        }

        protected virtual void OnPropertyChanged([CallerMemberName] string propertyName = null)
        {
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
        }
    }
}