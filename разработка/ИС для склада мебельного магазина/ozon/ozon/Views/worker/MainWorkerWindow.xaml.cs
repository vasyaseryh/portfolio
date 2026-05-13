using System;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using ozon.Models;
using System.IO;
using System.Windows.Media;
using System.Windows.Data;

namespace ozon.Views
{
    public partial class MainWorkerWindow : Window
    {
        public MainWorkerWindow()
        {
            InitializeComponent();
            SetTextQualityOptions();
            LoadOrders();
        }

        private void SetTextQualityOptions()
        {
            TextOptions.SetTextFormattingMode(this, TextFormattingMode.Display);
            TextOptions.SetTextRenderingMode(this, TextRenderingMode.ClearType);
        }

        private void LoadOrders()
        {
            using (var db = new OzonContext())
            {
                var ProductsOrders = from order in db.Orders
                                     join product in db.Products on order.ProductId equals product.Id
                                     where order.Address == StaticUser.Address
                                     select new
                                     {
                                         Id = order.Id,
                                         Name = product.Name,
                                         Description = product.Description,
                                         ImgUrl = product.ImgUrl,
                                         Quantity = order.Quantity,
                                         Address = order.Address,
                                         Status = order.Status,
                                         Price = order.Price,
                                         Location = order.location,
                                         CreatedDate = order.Create,
                                         DeliveryDate = order.DeliveryTime,
                                         ShipmentDate = order.ShipmentTime
                                     };

                var result = ProductsOrders.AsEnumerable()
                                          .Select(o => new
                                          {
                                              o.Id,
                                              o.Name,
                                              o.Description,
                                              ImgUrl = GetImagePath(o.ImgUrl),
                                              o.Quantity,
                                              o.Address,
                                              o.Status,
                                              o.Price,
                                              o.Location,
                                              o.CreatedDate,
                                              o.DeliveryDate,
                                              o.ShipmentDate,
                                              StatusColor = GetStatusColor(o.Status),
                                              ShowLocation = ShouldShowLocation(o.Status),
                                              CreatedDateString = FormatDate(o.CreatedDate),
                                              DeliveryDateString = FormatDate(o.DeliveryDate),
                                              ShipmentDateString = FormatDate(o.ShipmentDate)
                                          })
                                          .ToList();

                productsList.ItemsSource = result;
            }
        }

        private string GetImagePath(string imgUrl)
        {
            try
            {
                return Path.GetFullPath(imgUrl);
            }
            catch
            {
                return imgUrl;
            }
        }

        private string GetStatusColor(string status)
        {
            if (string.IsNullOrEmpty(status)) return "#6C757D";

            var statusColors = new System.Collections.Generic.Dictionary<string, string>
            {
                {"в пути на склад", "#4CAF50"},
                {"на складе", "#2196F3"},
                {"ожидает отгрузки", "#6C757D"},
                {"отменен", "#F44336"},
                {"в пути к клиенту", "#4CAF50"},
            };

            string statusLower = status.ToLower();
            return statusColors.ContainsKey(statusLower) ? statusColors[statusLower] : "#6C757D";
        }

        private bool ShouldShowLocation(string status)
        {
            if (string.IsNullOrEmpty(status)) return false;

            string statusLower = status.ToLower();
            return statusLower == "на складе" || statusLower == "ожидает отгрузки";
        }

        private string FormatDate(DateTime? date)
        {
            if (date.HasValue)
            {
                return date.Value.ToString("dd.MM.yyyy HH:mm");
            }
            return "не указана";
        }

        private void OnProductSelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            if (productsList.SelectedItem != null)
            {
                dynamic selectedItem = productsList.SelectedItem;
                int id = selectedItem.Id;

                var changeWindow = new ChangeStatusWindow(id);
                changeWindow.ShowDialog();

                // Обновляем список после закрытия окна изменения статуса
                if (changeWindow.DialogResult == true)
                {
                    LoadOrders();
                }

                // Сбрасываем выделение
                productsList.SelectedItem = null;
            }
        }

        private void Back_Click(object sender, RoutedEventArgs e)
        {
            LoginWindow loginWindow = new LoginWindow();
            loginWindow.Show();
            Close();
        }

        private void SearchTextBox_TextChanged(object sender, TextChangedEventArgs e)
        {
            using (var db = new OzonContext())
            {
                var searchTerm = SearchTextBox.Text.Trim().ToLower();

                var ProductsOrders = from order in db.Orders
                                     join product in db.Products on order.ProductId equals product.Id
                                     where order.Address == StaticUser.Address &&
                                           (string.IsNullOrEmpty(searchTerm) ||
                                            product.Name.ToLower().Contains(searchTerm))
                                     select new
                                     {
                                         Id = order.Id,
                                         Name = product.Name,
                                         Description = product.Description,
                                         ImgUrl = product.ImgUrl,
                                         Quantity = order.Quantity,
                                         Address = order.Address,
                                         Status = order.Status,
                                         Price = order.Price,
                                         Location = order.location,
                                         CreatedDate = order.Create,
                                         DeliveryDate = order.DeliveryTime,
                                         ShipmentDate = order.ShipmentTime
                                     };

                var result = ProductsOrders.AsEnumerable()
                                          .Select(o => new
                                          {
                                              o.Id,
                                              o.Name,
                                              o.Description,
                                              ImgUrl = GetImagePath(o.ImgUrl),
                                              o.Quantity,
                                              o.Address,
                                              o.Status,
                                              o.Price,
                                              o.Location,
                                              o.CreatedDate,
                                              o.DeliveryDate,
                                              o.ShipmentDate,
                                              StatusColor = GetStatusColor(o.Status),
                                              ShowLocation = ShouldShowLocation(o.Status),
                                              CreatedDateString = FormatDate(o.CreatedDate),
                                              DeliveryDateString = FormatDate(o.DeliveryDate),
                                              ShipmentDateString = FormatDate(o.ShipmentDate)
                                          })
                                          .ToList();

                productsList.ItemsSource = result;
            }
        }
    }

    // Конвертер для преобразования bool в Visibility
    public class BooleanToVisibilityConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, System.Globalization.CultureInfo culture)
        {
            return (value is bool && (bool)value) ? Visibility.Visible : Visibility.Collapsed;
        }

        public object ConvertBack(object value, Type targetType, object parameter, System.Globalization.CultureInfo culture)
        {
            return value is Visibility && (Visibility)value == Visibility.Visible;
        }
    }
}