using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Shapes;

namespace BootsApp
{
    /// <summary>
    /// Логика взаимодействия для AdminOrderWindow.xaml
    /// </summary>
    public partial class AdminOrderWindow : Window
    {
        public static bootsEntities2 _context;
        public AdminOrderWindow()
        {
            InitializeComponent();
            _context = bootsEntities2.GetContext();
            DgOrder.ItemsSource = _context.Заказ_import.ToList();

        }

        private void AddButt(object sender, RoutedEventArgs e)
        {
            new AdminAddOrderWindow().Show();
        }

        private void ChangeButt(object sender, RoutedEventArgs e)
        {
            var order = DgOrder.SelectedItem;
            new AdminAddOrderWindow(order as Заказ_import).Show();
        }

        private void RefreshButt(object sender, RoutedEventArgs e)
        {
            _context = bootsEntities2.GetContext();
            DgOrder.ItemsSource = _context.Заказ_import.ToList();
        }

        private void DelButt(object sender, RoutedEventArgs e)
        {
            var item = DgOrder.SelectedItem;
            _context.Заказ_import.Remove(item as Заказ_import);
            _context.SaveChanges();
        }

        private void GoHomeButt(object sender, RoutedEventArgs e)
        {
            new MainWindow().Show();
            this.Close();
        }


        private void AdminUsersButt(object sender, RoutedEventArgs e)
        {
            new AdminMainWindow().Show();
            this.Close();
        }

        private void TextBox_TextChanged(object sender, TextChangedEventArgs e)
        {
            DgOrder.ItemsSource = _context.Заказ_import.ToList().Where(x =>
                x.Дата_заказа.ToString().ToLower().Contains(SearchBox.Text.ToLower()) ||
                x.Дата_доставки.ToString().ToLower().Contains(SearchBox.Text.ToLower()) || 
                x.ФИО_авторизованного_клиента.ToLower().Contains(SearchBox.Text.ToLower()) ||
                x.Статусы.название.ToLower().Contains(SearchBox.Text.ToLower()) ||
                x.Пункты_выдачи_import.ПолныйАдрес.ToLower().Contains(SearchBox.Text.ToLower())
            ).ToList();
        }

        private void AdminTovarButt(object sender, RoutedEventArgs e)
        {
            new AdminTovarWindow().Show();
            this.Close();
        }
    }
}
